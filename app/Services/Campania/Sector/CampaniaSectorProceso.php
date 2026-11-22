<?php

namespace App\Services\Campania\Sector;

use App\Exceptions\ErrorControlado;
use App\Exceptions\SiembraConEvaluaciones;
use App\Models\CampaniaSector;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\Siembra;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraProceso;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Siembras de una campaña (grupos de hileras del mapa de etapas) y sus etapas: detección con lo registrado y corrección
 * a mano.
 *
 * Una campaña tiene más de una siembra solo si parte del campo se sembró en otra fecha (Lucio, 16/11/2026: campos
 * sembrados a 4 m y meses después rellenados a 2 m en el intermedio: dos edades, dos infestaciones, dos cosechas; la
 * campaña se cierra con la última cosecha). Las tandas de infestación o de poda NO son motivo de otro grupo.
 *
 * Detección: una sola siembra, la del inicio (tanda grande de labores de siembra antes de infestar, o la última
 * plantación del registro). Los rellenos NO se detectan solos (en las labores un relleno y un replante se ven igual):
 * se proponen como "posibles rellenos" en la tarjeta y una persona los confirma (o al registrar horas). Con varias
 * siembras, cada tanda de infestación, cosecha… va a la siembra más antigua que todavía no pasó por esa etapa.
 *
 * Corrección: con más de una siembra, cada una necesita su fecha de siembra (distinta); no se puede dejar fuera de su
 * etapa una labor ya registrada que antes sí caía en ella. Cada siembra queda enlazada con el registro de siembras
 * (Campo → Siembras) con su origen y motivo (sincronizarRegistro).
 */
class CampaniaSectorProceso
{
    public function __construct(private CampaniaSectorConsulta $consulta)
    {
    }

    /** @return int siembras creadas (0 si se respetó una corrección manual) */
    public function detectar(CampoCampania $c, bool $forzar = false): int
    {
        if (!$c->fecha_inicio) {
            return 0;
        }
        if (!$forzar && $c->sectores()->where('origen', 'manual')->exists()) {
            return 0;
        }
        $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $inicioCampania = Carbon::parse($c->fecha_inicio)->toDateString();
        $registros = $this->consulta->registros($c);

        // Días de cada etapa y tandas de lo que la empieza (colocar infestadores, podar), con las labores del registro
        // diario y los hitos del módulo de cochinilla (infestaciones, cosechas: tienen historia más antigua)
        $dias = [];
        $inicios = [];
        $jornalesSiembra = [];
        $laboresSiembra = []; // fecha => [codigo · nombre => jornales] (el detalle de por qué existe cada siembra)
        foreach ($registros as $r) {
            $dias[$r['etapa']][$r['fecha']] = true;
            if (CampaniaSectorReglas::iniciaGrupo($r['etapa'], $r['nombre'])) {
                $inicios[$r['etapa']][$r['fecha']] = true;
            }
            if ($r['etapa'] === 'siembra') {
                $jornalesSiembra[$r['fecha']] = ($jornalesSiembra[$r['fecha']] ?? 0) + $r['trabajadores'];
                $clave = "{$r['labor']} · {$r['nombre']}";
                $laboresSiembra[$r['fecha']][$clave] = ($laboresSiembra[$r['fecha']][$clave] ?? 0) + $r['trabajadores'];
            }
        }
        foreach ($this->consulta->eventosCochinilla($c) as $e) {
            $dias[$e['etapa']][$e['fecha']] = true;
            if ($e['inicia']) {
                $inicios[$e['etapa']][$e['fecha']] = true;
            }
        }

        // Tandas de labores de siembra con sus jornales y labores
        $primeraInfestacionOCosecha = collect(['infestacion', 'reinfestacion', 'cosecha_madres', 'cosecha'])
            ->flatMap(fn($e) => array_keys($dias[$e] ?? []))->min();
        $diasSiembra = array_keys($dias['siembra'] ?? []);
        $tandasSiembra = array_map(function ($tanda) use ($jornalesSiembra, $laboresSiembra) {
            $labores = [];
            foreach ($tanda as $f) {
                foreach ($laboresSiembra[$f] ?? [] as $labor => $j) {
                    $labores[$labor] = ($labores[$labor] ?? 0) + $j;
                }
            }
            arsort($labores);
            return ['dias' => $tanda, 'desde' => $tanda[0], 'hasta' => end($tanda),
                'jornales' => array_sum(array_map(fn($f) => $jornalesSiembra[$f] ?? 0, $tanda)), 'labores' => $labores];
        }, $this->tandas($diasSiembra));
        $grande = fn($t) => $t['jornales'] >= CampaniaSectorReglas::RELLENO_MIN_JORNALES;

        // Siembra del inicio: una tanda grande de siembra antes de infestar (el campo se plantó en esta campaña) o, si
        // no, la última plantación del registro antes del inicio. Los RELLENOS no se crean solos: con las labores no se
        // distingue un relleno de un replante (los rellenos de los campos 2 y 18 se registraron como "Replante"); se
        // muestran como posibles rellenos en la tarjeta y una persona los confirma (CampaniaSectorConsulta::posiblesRellenos).
        $baseTanda = collect($tandasSiembra)->first(fn($t) => $grande($t) && (!$primeraInfestacionOCosecha || $t['desde'] < $primeraInfestacionOCosecha));
        $tandasRelleno = [];
        // Labores de siembra después de infestar: replantes o rellenos sin confirmar, no mueven la etapa
        $fechasRelleno = $primeraInfestacionOCosecha ? array_values(array_filter($diasSiembra, fn($f) => $f > $primeraInfestacionOCosecha)) : [];
        $registrada = Siembra::plantaciones()->where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '<=', $inicioCampania)
            ->max('fecha_siembra');
        $base = $baseTanda['desde'] ?? ($registrada ? Carbon::parse($registrada)->toDateString() : null);

        $fechasSiembra = array_merge([$base], array_map(fn($t) => $t['desde'], $tandasRelleno));
        $fechasSiembra = array_slice($fechasSiembra, 0, max(1, min(CampaniaSectorReglas::MAX_GRUPOS, $hileras)));
        // Por qué existe cada siembra (se guarda en el registro de siembras si no estaba)
        $porque = [];
        foreach (array_merge([$baseTanda], $tandasRelleno) as $k => $t) {
            if ($t && isset($fechasSiembra[$k])) {
                $porque[$fechasSiembra[$k]] = [
                    'motivo' => ($k === 0 ? 'Plantación detectada' : 'Relleno detectado') . ' por labores de siembra del '
                        . Carbon::parse($t['desde'])->format('d/m/Y') . ($t['hasta'] !== $t['desde'] ? ' al ' . Carbon::parse($t['hasta'])->format('d/m/Y') : '')
                        . " ({$t['jornales']} jornales)",
                    'detalle' => ['desde' => $t['desde'], 'hasta' => $t['hasta'], 'jornales' => $t['jornales'], 'dias' => count($t['dias']), 'labores' => $t['labores']],
                ];
            }
        }
        $n = count($fechasSiembra);
        $rangos = CampaniaSectorReglas::repartirHileras($hileras, $n);

        // Etapas de cada siembra
        $etapasPorSiembra = array_fill(0, $n, []);
        foreach (array_keys(CampaniaSectorReglas::ETAPAS) as $etapa) {
            if (empty($dias[$etapa])) {
                continue;
            }
            if ($n === 1) {
                $diasEtapa = array_keys($dias[$etapa]);
                if ($etapa === 'siembra') {
                    $diasEtapa = array_values(array_diff($diasEtapa, $fechasRelleno));
                }
                if ($diasEtapa) {
                    $etapasPorSiembra[0][$etapa] = min($diasEtapa);
                }
                continue;
            }
            if ($etapa === 'siembra') {
                $antesDelRelleno = array_filter(array_keys($dias['siembra']), fn($f) => !in_array($f, $fechasRelleno, true));
                if ($antesDelRelleno) {
                    $etapasPorSiembra[0]['siembra'] = min($antesDelRelleno);
                }
                for ($k = 1; $k < $n; $k++) {
                    $etapasPorSiembra[$k]['siembra'] = max($fechasSiembra[$k], $inicioCampania);
                }
                continue;
            }
            foreach ($this->tandas(array_keys(($inicios[$etapa] ?? []) ?: $dias[$etapa])) as $tanda) {
                for ($k = 0; $k < $n; $k++) {
                    $sembrada = $k === 0 || $fechasSiembra[$k] <= $tanda[0];
                    if ($sembrada && !isset($etapasPorSiembra[$k][$etapa])) {
                        $etapasPorSiembra[$k][$etapa] = $tanda[0];
                        break;
                    }
                }
            }
        }

        // Las siembras que quedan conservan su id (las evaluaciones apuntan a él); una que sobra no puede tener evaluaciones
        $existentes = $c->sectores()->get()->values();
        $sobran = $existentes->slice(count($rangos));
        $vinculadas = app(EvaluacionSiembraConsulta::class)->evaluaciones($c, $sobran->pluck('id')->all());
        if ($vinculadas) {
            throw new ErrorControlado('No se volvió a detectar: ' . $sobran->whereIn('id', array_column($vinculadas, 'sector_id'))->pluck('nombre')->implode(', ')
                . ' tiene ' . count($vinculadas) . ' evaluación(es). Quita esa siembra desde "Corregir hileras y etapas" para decidir qué hacer con ellas.');
        }

        DB::transaction(function () use ($c, $rangos, $fechasSiembra, $etapasPorSiembra, $existentes, $sobran) {
            foreach ($rangos as $k => [$desde, $hasta]) {
                $datos = [
                    'campo_campania_id' => $c->id,
                    'nombre' => CampaniaSectorReglas::nombreSiembra($k + 1),
                    'fecha_siembra' => $fechasSiembra[$k] ?? null,
                    'hilera_desde' => $desde,
                    'hilera_hasta' => $hasta,
                    'origen' => 'detectado',
                ];
                $sector = $existentes[$k] ?? null;
                if ($sector) {
                    $sector->update($datos);
                } else {
                    $sector = CampaniaSector::create($datos);
                }
                $sector->etapas()->delete();
                foreach ($etapasPorSiembra[$k] as $etapa => $fecha) {
                    $sector->etapas()->create(['etapa' => $etapa, 'fecha_desde' => $fecha]);
                }
            }
            CampaniaSector::whereIn('id', $sobran->pluck('id'))->delete();
        });
        $this->sincronizarRegistro($c, 'deteccion', null, $porque);
        return count($rangos);
    }

    /**
     * Enlaza cada siembra del mapa con el registro de siembras (misma fuente): si no hay un registro del campo con esa
     * fecha se crea (la 1ª como plantación, las demás como relleno) con su origen, motivo y detalle. Los rellenos de la
     * campaña que ya no están en el mapa (se quitaron o se cambió su fecha) se borran del registro, salvo los
     * registrados a mano en Campo → Siembras.
     *
     * @param array $porque fecha_siembra => ['motivo', 'detalle'] (si no, se usa $motivo)
     */
    public function sincronizarRegistro(CampoCampania $c, string $origen, ?string $motivo = null, array $porque = []): void
    {
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $usuario = auth()->user()?->name ?? 'Sistema';
        DB::transaction(function () use ($c, $origen, $motivo, $porque, $inicio, $usuario) {
            foreach ($c->sectores()->orderBy('hilera_desde')->get()->values() as $k => $sector) {
                if (!$sector->fecha_siembra) {
                    $sector->update(['siembra_id' => null]);
                    continue;
                }
                $fecha = $sector->fecha_siembra->toDateString();
                $siembra = Siembra::where('campo_nombre', $c->campo)->whereDate('fecha_siembra', $fecha)->first();
                if (!$siembra) {
                    $tipo = $k === 0 ? 'siembra' : 'relleno';
                    // La 1ª siembra sin plantación en el registro toma su propio motivo (no el del relleno que se agregó)
                    if ($k === 0 && !isset($porque[$fecha])) {
                        $porque[$fecha] = ['motivo' => "Siembra con la que empezó la campaña {$c->nombre_campania}"
                            . ($fecha === $inicio ? ' (sin plantación en el registro: se tomó el inicio de la campaña)' : ''), 'detalle' => null];
                    }
                    $siembra = Siembra::create([
                        'campo_nombre' => $c->campo,
                        'campo_campania_id' => $tipo === 'relleno' || $fecha >= $inicio ? $c->id : null,
                        'fecha_siembra' => $fecha,
                        'tipo' => $tipo,
                        'origen' => $origen,
                        'motivo' => mb_substr((string) ($porque[$fecha]['motivo'] ?? $motivo ?? ''), 0, 255) ?: null,
                        'detalle' => $porque[$fecha]['detalle'] ?? null,
                        'creado_por' => $usuario,
                    ]);
                    AuditoriaServicio::registrar(Siembra::class, $siembra->id, 'crear', null, $siembra->toArray(),
                        "{$siembra->tipo} del campo {$c->campo} desde la campaña {$c->nombre_campania}");
                }
                if ((int) $sector->siembra_id !== (int) $siembra->id) {
                    $sector->update(['siembra_id' => $siembra->id]);
                }
            }
            // Lo que el sistema creó para esta campaña y ya no está en el mapa
            foreach (Siembra::where('campo_campania_id', $c->id)->where('origen', '<>', 'registro')->whereDoesntHave('sectores')->get() as $huerfana) {
                AuditoriaServicio::registrar(Siembra::class, $huerfana->id, 'eliminar', $huerfana->toArray(), null,
                    "Ya no está en el mapa de etapas de {$c->nombre_campania} (campo {$c->campo})");
                $huerfana->delete();
            }
        });
    }

    /** Enlaza todas las campañas con el registro de siembras (después de migrar). */
    public function sincronizarTodas(): int
    {
        $n = 0;
        CampoCampania::query()->whereNotNull('fecha_inicio')->whereHas('sectores')->orderBy('id')->chunkById(100, function ($campanias) use (&$n) {
            foreach ($campanias as $c) {
                $this->sincronizarRegistro($c, 'deteccion');
                $n++;
            }
        });
        return $n;
    }

    /** @return array{campanias:int, con_varios_grupos:int, respetadas:int} */
    public function detectarTodas(bool $forzar = false): array
    {
        $n = $varios = $respetadas = 0;
        CampoCampania::query()->whereNotNull('fecha_inicio')->orderBy('id')->chunkById(100, function ($campanias) use ($forzar, &$n, &$varios, &$respetadas) {
            foreach ($campanias as $c) {
                try {
                    $grupos = $this->detectar($c, $forzar);
                } catch (ErrorControlado) {
                    $grupos = 0; // Tiene evaluaciones en una siembra que la detección quitaría: se respeta
                }
                $grupos === 0 ? $respetadas++ : $n++;
                if ($grupos > 1) {
                    $varios++;
                }
            }
        });
        return ['campanias' => $n, 'con_varios_grupos' => $varios, 'respetadas' => $respetadas];
    }

    /**
     * Guarda las hileras del campo y las siembras con sus etapas (corrección a mano).
     *
     * @param array $sectores [['nombre', 'fecha_siembra', 'hilera_desde', 'hilera_hasta', 'etapas' => [['etapa', 'fecha_desde'], …]], …]
     * @param string $origen de una siembra nueva en el registro: mapa | tarjeta | horas | evaluacion
     * @param array $decisiones qué pasa con las evaluaciones de las siembras que se quitan:
     *                          clave => ['accion' => 'eliminar'] o ['accion' => 'mover', 'destino' => clave de la siembra]
     * @throws SiembraConEvaluaciones si se quita una siembra con evaluaciones sin decidir
     */
    public function guardar(CampoCampania $c, int $hileras, array $sectores, ?string $motivo = null, string $origen = 'mapa', array $decisiones = []): void
    {
        $limpios = $this->validar($c, $hileras, $sectores);

        // Candado: una siembra con evaluaciones no se quita sin decidir qué pasa con ellas (en campaña cerrada, nunca)
        $quedan = array_values(array_filter(array_column($limpios, 'id')));
        $quitadas = $c->sectores()->pluck('id')->diff($quedan)->values()->all();
        $vinculadas = app(EvaluacionSiembraConsulta::class)->evaluaciones($c, $quitadas);
        if ($vinculadas) {
            EvaluacionSiembraConsulta::exigirAbierta($c);
            $sinDecidir = array_values(array_filter($vinculadas, fn($e) => !in_array($decisiones[$e['clave']]['accion'] ?? null, ['mover', 'eliminar'], true)));
            if ($sinDecidir) {
                throw new SiembraConEvaluaciones($vinculadas);
            }
        }

        // No dejar fuera de su etapa labores que hoy sí están en ella
        $registros = $this->consulta->registros($c);
        $antes = $this->consulta->sectores($c);
        $fueraAntes = $this->consulta->fueraDeEtapa($antes, $registros)->map(fn($r) => "{$r['fecha']}|{$r['labor']}")->flip();
        $nuevasFuera = $this->consulta->fueraDeEtapa($limpios, $registros)->reject(fn($r) => isset($fueraAntes["{$r['fecha']}|{$r['labor']}"]));
        if ($nuevasFuera->isNotEmpty()) {
            $detalle = $nuevasFuera->groupBy('familia')->map(function ($g, $familia) {
                $fechas = $g->pluck('fecha')->unique()->sort()->values();
                $lista = $fechas->take(4)->map(fn($f) => Carbon::parse($f)->format('d/m'))->implode(', ') . ($fechas->count() > 4 ? ' y ' . ($fechas->count() - 4) . ' día(s) más' : '');
                return CampaniaSectorReglas::FAMILIAS[$familia] . " el {$lista} (" . $g->pluck('labor')->unique()->take(3)->map(fn($l) => $l . ' · ' . $g->firstWhere('labor', $l)['nombre'])->implode(', ') . ')';
            })->implode('; ');
            throw new ErrorControlado("No se guardó: hay labores registradas que quedarían fuera de su etapa: {$detalle}. "
                . 'Alguna siembra debe estar en esa etapa en esas fechas (o corrige primero la labor en el registro diario).');
        }

        DB::transaction(function () use ($c, $hileras, $limpios, $antes, $motivo, $quitadas, $vinculadas, $decisiones) {
            $campoAntes = (int) Campo::whereKey($c->campo)->value('hileras');
            if ($campoAntes !== $hileras) {
                Campo::whereKey($c->campo)->update(['hileras' => $hileras]);
            }
            // Cada siembra conserva su id al editarla: las evaluaciones siguen en ella aunque cambien sus hileras o fecha
            $sectorPorClave = [];
            foreach ($limpios as $s) {
                $datos = [
                    'campo_campania_id' => $c->id,
                    'nombre' => $s['nombre'],
                    'fecha_siembra' => $s['fecha_siembra'],
                    'hilera_desde' => $s['hilera_desde'],
                    'hilera_hasta' => $s['hilera_hasta'],
                    'origen' => 'manual',
                ];
                $sector = !empty($s['id']) ? CampaniaSector::find($s['id']) : null;
                if ($sector) {
                    $sector->update($datos);
                } else {
                    $sector = CampaniaSector::create($datos);
                }
                $sector->etapas()->delete();
                foreach ($s['etapas'] as $e) {
                    $sector->etapas()->create($e);
                }
                if ($s['clave']) {
                    $sectorPorClave[$s['clave']] = $sector->id;
                }
            }
            if ($vinculadas) {
                app(EvaluacionSiembraProceso::class)->aplicarDecisiones($c, $vinculadas, $decisiones, $sectorPorClave);
            }
            CampaniaSector::whereIn('id', $quitadas)->delete();
            AuditoriaServicio::registrar(CampoCampania::class, $c->id, 'editar',
                ['hileras' => $campoAntes, 'siembras' => $this->resumen($antes)],
                ['hileras' => $hileras, 'siembras' => $this->resumen($limpios)],
                mb_substr("Mapa de etapas de {$c->nombre_campania} (campo {$c->campo})" . ($motivo ? ". {$motivo}" : ''), 0, 255));
        });
        $this->sincronizarRegistro($c, $origen, $motivo);
    }

    /**
     * Agrega una siembra (relleno) sembrada en $fecha: queda en siembra desde ese día y las hileras se reparten parejo
     * (se corrige en el mapa). Desde la tarjeta de la campaña o al registrar horas.
     */
    public function agregarSiembra(CampoCampania $c, string $fecha, ?string $motivo = null, string $origen = 'tarjeta'): void
    {
        // Vacía no es "hoy" (Carbon::parse('') daría hoy): la fecha de siembra es obligatoria
        if (trim($fecha) === '') {
            throw new ErrorControlado('Elige la fecha de siembra del relleno.');
        }
        try {
            $fecha = Carbon::parse($fecha)->toDateString();
        } catch (\Throwable) {
            throw new ErrorControlado('Elige la fecha de siembra del relleno.');
        }
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = CampaniaSectorConsulta::fin($c);
        if ($fecha <= $inicio || $fecha > $fin) {
            throw new ErrorControlado('La fecha de siembra del relleno debe estar dentro de la campaña ('
                . Carbon::parse($inicio)->format('d/m/Y') . ' a ' . Carbon::parse($fin)->format('d/m/Y') . ').');
        }
        $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $sectores = self::conSiembraNueva($this->consulta->sectores($c), $fecha, $hileras, $c);
        $texto = 'Relleno sembrado el ' . Carbon::parse($fecha)->format('d/m/Y') . (trim((string) $motivo) !== '' ? ': ' . trim($motivo) : '');
        $this->guardar($c, $hileras, $sectores, $texto, $origen);
    }

    /** Las siembras con una más (relleno) en $fecha. Las anteriores sin fecha de siembra toman la del registro o el inicio. */
    public static function conSiembraNueva(array $sectores, string $fecha, int $hileras, CampoCampania $c): array
    {
        if (count($sectores) + 1 > $hileras) {
            throw new ErrorControlado("El campo {$c->campo} tiene {$hileras} hilera(s): no alcanzan para otra siembra. Aumenta las hileras en el mapa de etapas.");
        }
        $registro = Siembra::plantaciones()->where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '<', $fecha)->max('fecha_siembra');
        foreach ($sectores as $k => $s) {
            if (empty($s['fecha_siembra'])) {
                $sectores[$k]['fecha_siembra'] = $k === 0 && $registro ? Carbon::parse($registro)->toDateString() : Carbon::parse($c->fecha_inicio)->toDateString();
            }
        }
        $sectores = collect($sectores)->sortBy('hilera_desde')->values()->all();
        $sectores[] = ['id' => null, 'nombre' => CampaniaSectorReglas::nombreSiembra(count($sectores) + 1), 'fecha_siembra' => $fecha,
            'hilera_desde' => 1, 'hilera_hasta' => 1, 'etapas' => [['etapa' => 'siembra', 'fecha_desde' => $fecha]]];
        foreach (CampaniaSectorReglas::repartirHileras($hileras, count($sectores)) as $k => [$desde, $hasta]) {
            $sectores[$k]['hilera_desde'] = $desde;
            $sectores[$k]['hilera_hasta'] = $hasta;
        }
        return $sectores;
    }

    /** Valida y ordena lo que viene del editor. */
    private function validar(CampoCampania $c, int $hileras, array $sectores): array
    {
        if ($hileras < 1 || $hileras > 500) {
            throw new ErrorControlado('El número de hileras debe estar entre 1 y 500.');
        }
        // Otras campañas del campo no pueden quedar con hileras que ya no existen
        $otra = CampaniaSector::whereHas('campania', fn($q) => $q->where('campo', $c->campo)->where('id', '<>', $c->id))
            ->where('hilera_hasta', '>', $hileras)->with('campania:id,nombre_campania')->first();
        if ($otra) {
            throw new ErrorControlado("La campaña {$otra->campania->nombre_campania} del campo {$c->campo} usa hasta la hilera {$otra->hilera_hasta}: "
                . 'corrige primero su mapa o deja al menos esas hileras.');
        }
        if (!$sectores) {
            throw new ErrorControlado('Debe haber al menos una siembra.');
        }
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = $c->fecha_fin ? Carbon::parse($c->fecha_fin)->toDateString() : null;
        $varias = count($sectores) > 1;
        $limpios = [];
        $ocupadas = [];
        $fechasSiembra = [];
        $idsPropios = $c->sectores()->pluck('id')->flip();
        foreach (array_values($sectores) as $i => $s) {
            $nombre = trim((string) ($s['nombre'] ?? '')) ?: CampaniaSectorReglas::nombreSiembra($i + 1);
            $desde = (int) ($s['hilera_desde'] ?? 0);
            $hasta = (int) ($s['hilera_hasta'] ?? 0);
            if ($desde < 1 || $hasta < $desde || $hasta > $hileras) {
                throw new ErrorControlado("{$nombre}: las hileras deben ir de 1 a {$hileras} (desde ≤ hasta).");
            }
            for ($h = $desde; $h <= $hasta; $h++) {
                if (isset($ocupadas[$h])) {
                    throw new ErrorControlado("La hilera {$h} está en {$ocupadas[$h]} y en {$nombre}: cada hilera va en una sola siembra.");
                }
                $ocupadas[$h] = $nombre;
            }

            // Fecha de siembra: con varias siembras es obligatoria y distinta (es lo único que justifica otro grupo)
            $fechaSiembra = trim((string) ($s['fecha_siembra'] ?? ''));
            if ($fechaSiembra !== '') {
                try {
                    $fechaSiembra = Carbon::parse($fechaSiembra)->toDateString();
                } catch (\Throwable) {
                    throw new ErrorControlado("{$nombre}: la fecha de siembra no es válida.");
                }
                if ($fechaSiembra > ($fin ?? now()->toDateString())) {
                    throw new ErrorControlado("{$nombre}: la fecha de siembra es posterior a la campaña.");
                }
            } elseif ($varias) {
                throw new ErrorControlado("{$nombre}: falta la fecha de siembra. Con más de una siembra, cada una necesita su fecha "
                    . '(un grupo de hileras solo existe porque se sembró en otra fecha).');
            }
            if ($fechaSiembra !== '' && isset($fechasSiembra[$fechaSiembra])) {
                throw new ErrorControlado("{$nombre} y {$fechasSiembra[$fechaSiembra]} tienen la misma fecha de siembra (" . Carbon::parse($fechaSiembra)->format('d/m/Y')
                    . '): si se sembraron el mismo día son una sola siembra.');
            }
            if ($fechaSiembra !== '') {
                $fechasSiembra[$fechaSiembra] = $nombre;
            }

            $etapas = [];
            foreach ($s['etapas'] ?? [] as $e) {
                $etapa = (string) ($e['etapa'] ?? '');
                $fecha = trim((string) ($e['fecha_desde'] ?? ''));
                if ($etapa === '' && $fecha === '') {
                    continue;
                }
                if (!isset(CampaniaSectorReglas::ETAPAS[$etapa])) {
                    throw new ErrorControlado("{$nombre}: elige la etapa.");
                }
                try {
                    $fecha = Carbon::parse($fecha)->toDateString();
                } catch (\Throwable) {
                    throw new ErrorControlado("{$nombre}: falta la fecha de " . CampaniaSectorReglas::nombre($etapa) . '.');
                }
                if ($fecha < $inicio || ($fin && $fecha > $fin)) {
                    throw new ErrorControlado("{$nombre}: " . CampaniaSectorReglas::nombre($etapa) . ' empieza el ' . Carbon::parse($fecha)->format('d/m/Y')
                        . ', fuera de la campaña (' . Carbon::parse($inicio)->format('d/m/Y') . ' a ' . ($fin ? Carbon::parse($fin)->format('d/m/Y') : 'hoy') . ').');
                }
                $etapas["{$etapa}|{$fecha}"] = ['etapa' => $etapa, 'fecha_desde' => $fecha];
            }
            $etapas = collect($etapas)->sortBy('fecha_desde')->values()->all();
            $idPropio = isset($s['id'], $idsPropios[$s['id']]) ? (int) $s['id'] : null;
            $limpios[] = ['id' => $idPropio, 'clave' => $s['clave'] ?? ($idPropio ? "s{$idPropio}" : null),
                'nombre' => mb_substr($nombre, 0, 40), 'fecha_siembra' => $fechaSiembra ?: null,
                'hilera_desde' => $desde, 'hilera_hasta' => $hasta, 'etapas' => $etapas];
        }
        return collect($limpios)->sortBy('hilera_desde')->values()->all();
    }

    /** Fechas separadas en tandas (más de DIAS_GRUPO días entre una y otra = otra tanda). */
    private function tandas(array $fechas): array
    {
        sort($fechas);
        $tandas = [];
        $anterior = null;
        foreach ($fechas as $f) {
            if (!$anterior || Carbon::parse($anterior)->diffInDays(Carbon::parse($f)) > CampaniaSectorReglas::DIAS_GRUPO) {
                $tandas[] = [];
            }
            $tandas[count($tandas) - 1][] = $f;
            $anterior = $f;
        }
        return $tandas;
    }

    private function resumen(array $sectores): array
    {
        return array_map(fn($s) => "{$s['nombre']}" . (!empty($s['fecha_siembra']) ? " (sembrada {$s['fecha_siembra']})" : '')
            . " (hileras {$s['hilera_desde']}–{$s['hilera_hasta']}): "
            . implode(', ', array_map(fn($e) => CampaniaSectorReglas::nombre($e['etapa']) . ' ' . $e['fecha_desde'], $s['etapas'] ?? [])), $sectores);
    }
}
