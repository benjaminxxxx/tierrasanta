<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\ResumenCostoDiario;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mantiene al día la mano de obra de resumen_costo_diarios (planilla, cuadrilla, riego, bonos y
 * mano de obra indirecta) para que el consolidado mensual solo tenga que leer la tabla.
 *
 * 1) Al guardar (registro diario, reporte semanal, bonificaciones, riego, generar planilla) se llama
 *    registrarCambio(): al terminar la petición se regeneran solo esas fechas.
 * 2) Al consolidar el mes, asegurarMes() compara fechas de modificación de las fuentes contra la
 *    última regeneración de cada día y rehace solo los días que cambiaron después (red de seguridad
 *    para lo que no pase por el paso 1, p. ej. cambios de campaña).
 */
class BddManoObraServicio
{
    /** Orígenes que regenera consolidarPlanillaEnRango (tipos null). */
    public const ORIGENES = ['planilla', 'cuadrilla', 'cuadrilla_bono', 'planilla_bono_productividad'];

    public const TIPO_TAREA = 'bdd-mano-obra';

    /** @var array<int, array{0:string,1:string}> rangos pendientes de esta petición */
    private static array $pendientes = [];
    private static bool $registrado = false;

    public function __construct(
        private ConsolidarCostoManoObraServicio $manoObra,
        private ConsolidarManoObraIndirectaServicio $indirecta,
    ) {
    }

    /**
     * Anota que cambió la mano de obra de una fecha o rango. Se procesa una sola vez al terminar
     * la petición (agrupa varios guardados y no demora la respuesta cuando el servidor lo permite).
     */
    public static function registrarCambio(string $fechaInicio, ?string $fechaFin = null, ?string $campo = null): void
    {
        $inicio = Carbon::parse($fechaInicio)->toDateString();
        $fin = Carbon::parse($fechaFin ?? $fechaInicio)->toDateString();
        // $campo: solo ese campo (p. ej. cambio de fechas de una campaña); null = todos los campos
        self::$pendientes[] = ($inicio <= $fin ? [$inicio, $fin] : [$fin, $inicio]) + [2 => $campo];

        if (!self::$registrado) {
            self::$registrado = true;
            app()->terminating(fn() => app(self::class)->procesarPendientes());
        }
    }

    /** Procesa lo anotado con registrarCambio() (también se puede llamar a mano). */
    public function procesarPendientes(): void
    {
        $pendientes = self::$pendientes;
        self::$pendientes = [];
        self::$registrado = false;

        // Se agrupa por campo ('' = todos) y se unen los rangos de cada grupo
        $porCampo = [];
        foreach ($pendientes as $pendiente) {
            $porCampo[$pendiente[2] ?? ''][] = [$pendiente[0], $pendiente[1]];
        }
        foreach ($porCampo as $campo => $rangos) {
            foreach ($this->unirRangos($rangos) as [$inicio, $fin]) {
                $this->regenerar($inicio, $fin, $campo !== '' ? (string) $campo : null);
            }
        }
    }

    /**
     * Deja el mes al día: por defecto solo los días desactualizados; $forzar rehace todo el mes.
     *
     * @return array{dias:int, filas:int, avisos:string[]}
     */
    public function asegurarMes(int $anio, int $mes, bool $forzar = false): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $dias = $forzar ? $this->diasDelRango($inicio, $fin) : $this->diasDesactualizados($anio, $mes);
        $filas = 0;
        foreach ($this->rangosContiguos($dias) as [$a, $b]) {
            $filas += $this->manoObra->consolidarPlanillaEnRango($a, $b);
        }

        // La indirecta depende de la tarifa del mes: siempre se recalcula (es rápida)
        $resultado = $this->indirecta->consolidarMes($anio, $mes);
        $this->cerrarTarea($anio, $mes);

        return ['dias' => count($dias), 'filas' => $filas, 'avisos' => $resultado['avisos']];
    }

    /**
     * Días del mes cuya mano de obra cambió después de su última regeneración en la BDD
     * (o que tienen fuentes y no tienen filas, o filas y ya no tienen fuentes).
     *
     * @return string[]
     */
    public function diasDesactualizados(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        // Última modificación de las fuentes por día
        $fuentes = collect([
            DB::table('plan_registros_diarios')->whereBetween('fecha', [$inicio, $fin])
                ->selectRaw('DATE(fecha) as dia, MAX(updated_at) as modificado'),
            DB::table('plan_detalles_horas as d')->join('plan_registros_diarios as r', 'r.id', '=', 'd.plan_reg_dia_id')
                ->whereBetween('r.fecha', [$inicio, $fin])->selectRaw('DATE(r.fecha) as dia, MAX(d.updated_at) as modificado'),
            DB::table('cuad_registros_diarios')->whereBetween('fecha', [$inicio, $fin])
                ->selectRaw('DATE(fecha) as dia, MAX(updated_at) as modificado'),
            DB::table('cuad_detalles_horas as d')->join('cuad_registros_diarios as r', 'r.id', '=', 'd.registro_diario_id')
                ->whereBetween('r.fecha', [$inicio, $fin])->selectRaw('DATE(r.fecha) as dia, MAX(d.updated_at) as modificado'),
            DB::table('cuad_bonos_actividades as b')->join('cuad_registros_diarios as r', 'r.id', '=', 'b.registro_diario_id')
                ->whereBetween('r.fecha', [$inicio, $fin])->selectRaw('DATE(r.fecha) as dia, MAX(b.updated_at) as modificado'),
            DB::table('actividades')->whereBetween('fecha', [$inicio, $fin])
                ->selectRaw('DATE(fecha) as dia, MAX(updated_at) as modificado'),
            DB::table('plan_actividad_bonos as b')->join('plan_registros_diarios as r', 'r.id', '=', 'b.registro_diario_id')
                ->whereBetween('r.fecha', [$inicio, $fin])->selectRaw('DATE(r.fecha) as dia, MAX(b.updated_at) as modificado'),
            DB::table('reg_registro_diario')->whereBetween('fecha', [$inicio, $fin])
                ->selectRaw('DATE(fecha) as dia, MAX(updated_at) as modificado'),
            DB::table('reg_resumen')->whereBetween('fecha', [$inicio, $fin])
                ->selectRaw('DATE(fecha) as dia, MAX(updated_at) as modificado'),
        ])->flatMap(fn($q) => $q->groupBy('dia')->get())
            ->groupBy('dia')
            ->map(fn($g) => $g->max('modificado'));

        // Cambios que afectan a todo el mes: tarifa de planilla y campañas que cruzan el mes
        $delMes = collect([
            DB::table('plan_mensual_personals as p')->join('plan_mensuales as m', 'm.id', '=', 'p.plan_mensual_id')
                ->where('m.mes', $mes)->where('m.anio', $anio)->max('p.updated_at'),
            DB::table('campos_campanias')->where('fecha_inicio', '<=', $fin)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
                ->max('updated_at'),
        ])->filter()->max();

        // Última regeneración de cada día en la BDD (las filas se borran y se crean de nuevo)
        $bdd = ResumenCostoDiario::whereIn('origen_tipo', self::ORIGENES)
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw('DATE(fecha) as dia, MIN(created_at) as gen')
            ->groupBy('dia')
            ->pluck('gen', 'dia');

        $dias = [];
        foreach ($fuentes->keys()->merge($bdd->keys())->unique() as $dia) {
            $generado = $bdd->get($dia);
            $modificado = collect([$fuentes->get($dia), $delMes])->filter()->max();
            if (!$generado || !$fuentes->has($dia) || ($modificado && $modificado > $generado)) {
                $dias[] = $dia;
            }
        }
        sort($dias);

        return $dias;
    }

    private function regenerar(string $inicio, string $fin, ?string $campo = null): void
    {
        try {
            $this->manoObra->consolidarPlanillaEnRango($inicio, $fin, null, $campo);
            foreach ($this->mesesDelRango($inicio, $fin) as [$anio, $mes]) {
                $this->indirecta->consolidarMes($anio, $mes);
            }
        } catch (\Throwable $e) {
            // No se interrumpe el guardado del usuario: queda como tarea pendiente y el consolidado
            // mensual lo rehace (asegurarMes detecta el día como desactualizado)
            Log::error("BDD mano de obra {$inicio}..{$fin}: " . $e->getMessage());
            foreach ($this->mesesDelRango($inicio, $fin) as [$anio, $mes]) {
                $this->abrirTarea($anio, $mes, $e->getMessage());
            }
        }
    }

    private function abrirTarea(int $anio, int $mes, string $motivo): void
    {
        $nombreMes = Carbon::create($anio, $mes, 1)->translatedFormat('F Y');
        // El mensaje de una QueryException trae el SQL completo (puede superar el tamaño de la columna): solo el motivo
        $motivo = mb_strimwidth(trim(preg_replace('/\s*\(Connection:.*$/s', '', $motivo)), 0, 500, '…');
        app(TareaPendienteServicio::class)->registrarOActualizar([
            'tipo' => self::TIPO_TAREA,
            'clave' => sprintf('%04d-%02d', $anio, $mes),
            'fecha_inicio' => Carbon::create($anio, $mes, 1)->toDateString(),
            'fecha_fin' => Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString(),
            'titulo' => "BDD de costos sin actualizar — {$nombreMes}",
            'descripcion' => "No se pudo actualizar la mano de obra en la BDD de costos: {$motivo}",
            'variante' => 'danger',
            'cantidad_afectados' => 1,
            'servicio' => self::class,
            'metodo_detectar' => null,
            'acciones' => [['titulo' => 'Reconstruir mes', 'metodo' => 'reconstruirMes', 'parametros' => ['anio' => $anio, 'mes' => $mes]]],
        ]);
    }

    /** Acción de la tarea pendiente. */
    public function reconstruirMes(int $anio, int $mes): array
    {
        return $this->asegurarMes($anio, $mes, true);
    }

    private function cerrarTarea(int $anio, int $mes): void
    {
        app(TareaPendienteServicio::class)->registrarOActualizar([
            'tipo' => self::TIPO_TAREA,
            'clave' => sprintf('%04d-%02d', $anio, $mes),
            'cantidad_afectados' => 0,
        ]);
    }

    /** @return array<int, array{0:string,1:string}> */
    private function unirRangos(array $rangos): array
    {
        usort($rangos, fn($a, $b) => strcmp($a[0], $b[0]));
        $unidos = [];
        foreach ($rangos as [$ini, $fin]) {
            $ultimo = count($unidos) - 1;
            if ($ultimo >= 0 && $ini <= Carbon::parse($unidos[$ultimo][1])->addDay()->toDateString()) {
                $unidos[$ultimo][1] = max($unidos[$ultimo][1], $fin);
            } else {
                $unidos[] = [$ini, $fin];
            }
        }
        return $unidos;
    }

    /** @return array<int, array{0:string,1:string}> */
    private function rangosContiguos(array $dias): array
    {
        return $this->unirRangos(array_map(fn($d) => [$d, $d], $dias));
    }

    private function diasDelRango(string $inicio, string $fin): array
    {
        $dias = [];
        for ($d = Carbon::parse($inicio); $d->toDateString() <= $fin; $d->addDay()) {
            $dias[] = $d->toDateString();
        }
        return $dias;
    }

    /** @return array<int, array{0:int,1:int}> [anio, mes] */
    private function mesesDelRango(string $inicio, string $fin): array
    {
        $meses = [];
        for ($d = Carbon::parse($inicio)->startOfMonth(); $d->toDateString() <= $fin; $d->addMonth()) {
            $meses[] = [$d->year, $d->month];
        }
        return $meses;
    }
}
