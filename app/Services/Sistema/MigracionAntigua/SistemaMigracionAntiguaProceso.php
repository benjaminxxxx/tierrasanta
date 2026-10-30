<?php

namespace App\Services\Sistema\MigracionAntigua;

use App\Exceptions\ErrorControlado;
use App\Services\Planilla\Empleados\PlanillaEmpleadosLegadoDefinicion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Pasa una base del sistema ANTIGUO (estructura hasta el 20/10/2026, p. ej. santarita_antiguo) a una base vacía con
 * la estructura actual (la conexión por defecto, recién migrada). La base antigua solo se lee: no se borra nada.
 *
 * 1. Copia tabla por tabla las columnas comunes conservando los id (las llaves foráneas siguen apuntando bien).
 *    Columnas que pasaron a NOT NULL: el valor por defecto. Planillas que ya no existen: general → oficina,
 *    mype / construccion → externa.
 * 2. Conversiones de estructura:
 *    - Planilla mensual: plan_mensual_personals se une a plan_mensual_detalles (los meses calculados con la planilla
 *      nueva quedan calculados; los demás quedan como lista sin calcular).
 *    - Empleados: remuneración, asignación, pensión y cuenta de pago salen de los contratos, sueldos y cargos antiguos
 *      (mismas reglas que el Excel de empleados de carga inicial).
 *    - Labores: rol en la campaña (las listas que se usaban); producción de campo con `produccion:preparar`.
 * 3. Nada se pierde: las tablas que ya no existen se copian tal cual (mismo nombre) y las que perdieron columnas se
 *    copian completas como legado_<tabla>.
 * 4. Informe: filas por tabla, llaves huérfanas y valores que recalculan los triggers.
 */
class SistemaMigracionAntiguaProceso
{
    /** Tablas que se copian después de otras por los triggers (además del orden de las llaves foráneas). */
    private const DESPUES_DE = [
        'cuad_registros_diarios' => ['cuad_costos_diarios_grupos'],
        'cuad_bonos_actividades' => ['actividad_metodos', 'cuad_registros_diarios', 'cuad_detalles_horas'],
        'cochinilla_infestaciones' => ['campos_campanias'],
        'cochinilla_ingresos' => ['campos_campanias'],
        'cochinilla_filtrados' => ['cochinilla_ingresos'],
        'cochinilla_venteados' => ['cochinilla_ingresos'],
    ];

    /** Tablas que un trigger llena al insertar en otra: se vacían antes de copiar las reales. */
    private const LLENADAS_POR_TRIGGER = ['cochinilla_filtrados', 'cochinilla_venteados'];

    /** Labores de cosecha y neutras (migración 2026_10_20_120000_add_rol_campania_to_labores_table). */
    private const LABORES_COSECHA = [41, 42, 52, 67, 68, 144, 145, 146];
    private const LABORES_NEUTRAS = [79, 80, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100,
        101, 102, 103, 104, 111, 112, 113, 114, 115, 116, 117, 122, 133, 140, 142, 143, 155, 158];

    private string $origen;
    private string $destino;
    /** @var callable(string): void */
    private $log;
    private array $avisos = [];

    /**
     * @param callable(string): void $log
     * @return array{tablas: array, legado: array, huerfanas: array, triggers: array, avisos: string[]}
     */
    public function ejecutar(string $origen, bool $vaciar, callable $log): array
    {
        $this->origen = $origen;
        $this->destino = DB::getDatabaseName();
        $this->log = $log;
        $this->validar($vaciar);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            if ($vaciar) {
                $this->vaciarDestino();
            }
            $colsO = $this->columnas($this->origen);
            $colsD = $this->columnas($this->destino);

            $tablas = [];
            foreach ($this->ordenDeCopia(array_keys($colsD)) as $t) {
                if (!isset($colsO[$t])) {
                    continue; // tabla nueva: queda vacía (o la llena una conversión)
                }
                if (in_array($t, self::LLENADAS_POR_TRIGGER, true)) {
                    DB::table($t)->delete();
                }
                $n = $t === 'plan_mensual_detalles' ? $this->copiarPlanillaMensual($colsO, $colsD) : $this->copiarTabla($t, $colsO[$t], $colsD[$t]);
                $tablas[$t] = $n;
            }

            $legado = $this->copiarLegado($colsO, $colsD);
            $this->rolCampaniaLabores();
            $this->particulasEmpleados();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        ($this->log)('Producción de campo (produccion:preparar)…');
        Artisan::call('produccion:preparar');
        ($this->log)(trim(Artisan::output()));

        return [
            'tablas' => $this->conteos($colsO, $colsD),
            'legado' => $legado,
            'huerfanas' => $this->huerfanas(),
            'triggers' => $this->diferenciasTriggers(),
            'avisos' => array_values(array_unique($this->avisos)),
        ];
    }

    // ------------------------------------------------------------------ validación

    private function validar(bool $vaciar): void
    {
        if ($this->origen === $this->destino) {
            throw new ErrorControlado('El origen y el destino son la misma base. Ejecuta con DB_DATABASE=<base nueva vacía>.');
        }
        if (!DB::selectOne('SELECT COUNT(*) c FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$this->origen])->c) {
            throw new ErrorControlado("La base {$this->origen} no existe.");
        }
        $antigua = DB::selectOne("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'plan_contratos' AND COLUMN_NAME = 'remuneracion_basica'", [$this->origen])->c;
        if (!$antigua) {
            throw new ErrorControlado("La base {$this->origen} no tiene la estructura antigua (plan_contratos.remuneracion_basica).");
        }
        $pendientes = collect(glob(database_path('migrations/*.php')))->map(fn($f) => basename($f, '.php'))
            ->diff(DB::table('migrations')->pluck('migration'));
        if ($pendientes->isNotEmpty()) {
            throw new ErrorControlado("La base {$this->destino} tiene migraciones pendientes: corre primero php artisan migrate en ella.");
        }
        if (!$vaciar) {
            $conDatos = collect($this->tablas($this->destino))->reject(fn($t) => $t === 'migrations')
                ->filter(fn($t) => DB::table($t)->exists())->values();
            if ($conDatos->isNotEmpty()) {
                throw new ErrorControlado("La base {$this->destino} no está vacía ({$conDatos->take(5)->implode(', ')}…). Usa --vaciar para empezar de nuevo.");
            }
        }
    }

    /** Deja vacías todas las tablas del destino (menos migrations) y quita las copias de legado de una corrida anterior. */
    private function vaciarDestino(): void
    {
        $esquema = $this->columnas($this->destino);
        foreach (array_keys($esquema) as $t) {
            if ($t === 'migrations') {
                continue;
            }
            // Tablas que no crea ninguna migración actual: copias de una corrida anterior
            if (str_starts_with($t, 'legado_') || !$this->tablaDeLaEstructuraActual($t)) {
                DB::statement("DROP TABLE `{$t}`");
                continue;
            }
            DB::table($t)->truncate();
        }
        ($this->log)('Destino vaciado.');
    }

    private function tablaDeLaEstructuraActual(string $t): bool
    {
        static $tablas = null;
        $tablas ??= collect(glob(database_path('migrations/*.php')))->flatMap(function ($f) {
            preg_match_all("/Schema::create\('([a-z0-9_]+)'/", file_get_contents($f), $m);
            return $m[1];
        })->unique()->all();
        return in_array($t, $tablas, true);
    }

    // ------------------------------------------------------------------ copia

    /** Copia las columnas comunes; las que ahora son NOT NULL toman su valor por defecto. */
    private function copiarTabla(string $t, array $colsO, array $colsD, array $expresiones = []): int
    {
        $comunes = array_values(array_intersect(array_keys($colsD), array_keys($colsO)));
        $select = array_map(fn($c) => $expresiones[$c] ?? $this->expresion($c, $colsO[$c], $colsD[$c]), $comunes);
        if ($t === 'plan_contratos') {
            $i = array_search('tipo_planilla', $comunes, true);
            if ($i !== false) {
                $select[$i] = "CASE WHEN `tipo_planilla` IN ('agraria','oficina') THEN `tipo_planilla` WHEN `tipo_planilla` = 'general' THEN 'oficina' ELSE 'externa' END";
            }
        }
        $lista = implode(', ', array_map(fn($c) => "`{$c}`", $comunes));
        DB::statement("INSERT INTO `{$this->destino}`.`{$t}` ({$lista}) SELECT " . implode(', ', $select) . " FROM `{$this->origen}`.`{$t}`");
        $n = DB::table($t)->count();
        ($this->log)("  {$t}: {$n}");
        return $n;
    }

    private function expresion(string $c, array $o, array $d): string
    {
        if (!$d['nulo'] && $o['nulo']) {
            $defecto = $d['defecto'] !== null ? DB::getPdo()->quote($d['defecto'])
                : (preg_match('/int|decimal|double|float/', $d['tipo']) ? '0' : "''");
            return "COALESCE(`{$c}`, {$defecto})";
        }
        return "`{$c}`";
    }

    /**
     * Planilla mensual: cada fila de plan_mensual_detalles (la lista del mes) con lo calculado de plan_mensual_personals
     * (mismo mes y empleado). Sin cálculo nuevo: queda sin calcular (los valores antiguos quedan en legado_).
     */
    private function copiarPlanillaMensual(array $colsO, array $colsD): int
    {
        $d = $colsO['plan_mensual_detalles'];
        $p = $colsO['plan_mensual_personals'] ?? [];
        $ancla = ['id', 'plan_mensual_id', 'plan_empleado_id', 'documento', 'nombres', 'orden', 'grupo', 'empleado_grupo_color', 'created_at', 'updated_at'];
        $select = [];
        foreach ($colsD['plan_mensual_detalles'] as $c => $def) {
            $defecto = $def['defecto'] !== null ? DB::getPdo()->quote($def['defecto']) : ($def['nulo'] ? 'NULL' : '0');
            $select[$c] = match (true) {
                $c === 'calculado_en' => $p ? 'IF(p.id IS NULL, NULL, p.updated_at)' : 'NULL',
                $c === 'orden_planilla' => $p ? 'p.orden' : 'NULL',
                $c === 'sistema_pension' => $p ? "COALESCE(p.sistema_pension, NULLIF(d.spp_snp, ''))" : "NULLIF(d.spp_snp, '')",
                in_array($c, $ancla, true) && isset($d[$c]) => "d.`{$c}`",
                isset($p[$c]) => isset($d[$c]) ? "IF(p.id IS NULL, d.`{$c}`, p.`{$c}`)" : "IF(p.id IS NULL, {$defecto}, p.`{$c}`)",
                isset($d[$c]) => "d.`{$c}`",
                default => $defecto,
            };
        }
        $union = $p ? "LEFT JOIN `{$this->origen}`.`plan_mensual_personals` p ON p.plan_mensual_id = d.plan_mensual_id AND p.plan_empleado_id = d.plan_empleado_id" : '';
        $lista = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($select)));
        DB::statement("INSERT INTO `plan_mensual_detalles` ({$lista}) SELECT " . implode(', ', $select) . " FROM `{$this->origen}`.`plan_mensual_detalles` d {$union}");
        $n = DB::table('plan_mensual_detalles')->count();
        $calc = DB::table('plan_mensual_detalles')->whereNotNull('calculado_en')->count();
        ($this->log)("  plan_mensual_detalles: {$n} ({$calc} con el cálculo de la planilla nueva)");
        if ($n > $calc) {
            $this->avisos[] = ($n - $calc) . ' filas de planilla mensual (meses calculados con la planilla antigua) quedan sin calcular; '
                . 'sus montos antiguos están en legado_plan_mensual_detalles. Si se necesita uno de esos meses, usa "Generar planilla".';
        }
        return $n;
    }

    /** Tablas que ya no existen: se copian con su nombre. Tablas que perdieron columnas: copia completa legado_<tabla>. */
    private function copiarLegado(array $colsO, array $colsD): array
    {
        $r = [];
        foreach ($colsO as $t => $cols) {
            $destino = !isset($colsD[$t]) ? $t : (array_diff_key($cols, $colsD[$t]) ? "legado_{$t}" : null);
            if (!$destino) {
                continue;
            }
            DB::statement("DROP TABLE IF EXISTS `{$destino}`");
            DB::statement("CREATE TABLE `{$destino}` LIKE `{$this->origen}`.`{$t}`");
            DB::statement("INSERT INTO `{$destino}` SELECT * FROM `{$this->origen}`.`{$t}`");
            $r[$destino] = DB::table($destino)->count();
        }
        ($this->log)('  Copias de legado: ' . count($r) . ' tablas.');
        return $r;
    }

    // ------------------------------------------------------------------ conversiones

    /** Rol de la labor en la campaña: las listas que se usaban (o las guardadas en configuracion). */
    private function rolCampaniaLabores(): void
    {
        $lista = function (string $config, array $defecto) {
            $valor = DB::table('configuracion')->where('codigo', $config)->value('valor');
            $codigos = $valor ? array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', $valor)))) : [];
            return array_values(array_diff($codigos ?: $defecto, $config === 'campania_labores_cosecha' ? [69] : []));
        };
        DB::table('labores')->whereIn('codigo', $lista('campania_labores_neutras', self::LABORES_NEUTRAS))->update(['rol_campania' => 'neutra']);
        DB::table('labores')->whereIn('codigo', $lista('campania_labores_cosecha', self::LABORES_COSECHA))->update(['rol_campania' => 'cosecha']);
        ($this->log)('  Labores: rol en la campaña asignado.');
    }

    /** Remuneración, asignación, pensión y cuenta de pago desde los contratos antiguos (reglas del Excel de carga inicial). */
    private function particulasEmpleados(): void
    {
        config(['database.connections.migracion_origen' => array_merge(config('database.connections.' . config('database.default')), ['database' => $this->origen])]);
        DB::purge('migracion_origen');
        $def = app(PlanillaEmpleadosLegadoDefinicion::class)->desde(DB::connection('migracion_origen'));
        $filas = $def->filasSistema();
        foreach ($def->avisosConversion() as $a) {
            $this->avisos[] = $a;
        }
        foreach (['asignaciones', 'remuneraciones', 'pensiones', 'pagos'] as $hoja) {
            $ok = 0;
            foreach ($filas[$hoja] as $fila) {
                try {
                    $def->guardar($hoja, $fila);
                    $ok++;
                } catch (\Throwable $e) {
                    $this->avisos[] = ucfirst($hoja) . " de {$fila['dni']} desde {$fila['desde']}: " . $e->getMessage();
                }
            }
            ($this->log)("  Empleados · {$hoja}: {$ok} de " . count($filas[$hoja]));
        }
    }

    // ------------------------------------------------------------------ informe

    private function conteos(array $colsO, array $colsD): array
    {
        $r = [];
        foreach (array_keys($colsD) as $t) {
            if ($t === 'migrations' || str_starts_with($t, 'legado_') || !$this->tablaDeLaEstructuraActual($t)) {
                continue;
            }
            $o = isset($colsO[$t]) ? DB::table("{$this->origen}.{$t}")->count() : null;
            $d = DB::table($t)->count();
            if ($o !== null || $d > 0) {
                $r[$t] = ['origen' => $o, 'destino' => $d];
            }
        }
        return $r;
    }

    /** Filas cuya llave foránea apunta a un registro que no existe (venían así de la base antigua). */
    private function huerfanas(): array
    {
        $r = [];
        $llaves = DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$this->destino]);
        foreach ($llaves as $k) {
            $n = DB::selectOne("SELECT COUNT(*) c FROM `{$k->t}` x WHERE x.`{$k->c}` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `{$k->rt}` y WHERE y.`{$k->rc}` = x.`{$k->c}`)")->c;
            if ($n) {
                $r[] = "{$k->t}.{$k->c} → {$k->rt}: {$n}";
            }
        }
        return $r;
    }

    /** Valores que los triggers recalculan al insertar: deben ser iguales a los de la base antigua. */
    private function diferenciasTriggers(): array
    {
        $o = $this->origen;
        $comparar = [
            ['cuad_registros_diarios', 'jornal_aplicado'], ['cuad_registros_diarios', 'horas_destajo'],
            ['cochinilla_infestaciones', 'campo_campania_id'], ['cochinilla_ingresos', 'campo_campania_id'],
            ['cochinilla_filtrados', 'cochinilla_ingreso_id'], ['cochinilla_venteados', 'cochinilla_ingreso_id'],
        ];
        $r = [];
        foreach ($comparar as [$t, $c]) {
            $n = DB::selectOne("SELECT COUNT(*) c FROM `{$t}` x JOIN `{$o}`.`{$t}` a ON a.id = x.id WHERE NOT (x.`{$c}` <=> a.`{$c}`)")->c;
            $r["{$t}.{$c}"] = $n;
        }
        return $r;
    }

    // ------------------------------------------------------------------ esquema

    /** @return array<string, array<string, array{tipo: string, nulo: bool, defecto: ?string}>> */
    private function columnas(string $db): array
    {
        $r = [];
        foreach (DB::select('SELECT TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty, IS_NULLABLE n, COLUMN_DEFAULT d, EXTRA e FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION', [$db]) as $x) {
            if (str_contains((string) $x->e, 'GENERATED')) {
                continue; // columnas calculadas: no se insertan
            }
            $r[$x->t][$x->c] = ['tipo' => $x->ty, 'nulo' => $x->n === 'YES', 'defecto' => $x->d];
        }
        unset($r['migrations']);
        return $r;
    }

    private function tablas(string $db): array
    {
        return array_column(DB::select("SELECT TABLE_NAME t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$db]), 't');
    }

    /** Orden de copia: primero las referidas por llaves foráneas y las que necesitan los triggers. */
    private function ordenDeCopia(array $tablas): array
    {
        $deps = array_fill_keys($tablas, []);
        foreach (DB::select('SELECT TABLE_NAME t, REFERENCED_TABLE_NAME rt FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL', [$this->destino]) as $k) {
            if ($k->t !== $k->rt && isset($deps[$k->t])) {
                $deps[$k->t][] = $k->rt;
            }
        }
        foreach (self::DESPUES_DE as $t => $antes) {
            if (isset($deps[$t])) {
                array_push($deps[$t], ...$antes);
            }
        }
        $orden = [];
        $visitando = [];
        $visitar = function ($t) use (&$visitar, &$orden, &$visitando, $deps) {
            if (isset($orden[$t]) || isset($visitando[$t])) {
                return; // ciclo: las llaves foráneas están desactivadas, no importa
            }
            $visitando[$t] = true;
            foreach ($deps[$t] ?? [] as $d) {
                $visitar($d);
            }
            $orden[$t] = true;
        };
        foreach ($tablas as $t) {
            $visitar($t);
        }
        return array_values(array_filter(array_keys($orden), fn($t) => in_array($t, $tablas, true)));
    }
}
