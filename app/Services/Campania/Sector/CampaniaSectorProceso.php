<?php

namespace App\Services\Campania\Sector;

use App\Exceptions\ErrorControlado;
use App\Models\CampaniaSector;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\Siembra;
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
 * Detección: la siembra del inicio (registro de siembras o primeras labores de siembra) y un relleno por cada tanda de
 * labores de siembra con al menos RELLENO_MIN_JORNALES después de que el campo ya se infestó o cosechó (una siembra
 * del registro durante la campaña no cuenta: suele ser una renovación). Las hileras se reparten parejo. Cada tanda de infestación, cosecha… va a la
 * siembra más antigua (ya sembrada en esa fecha) que todavía no pasó por esa etapa. Es una aproximación: se corrige a mano.
 *
 * Corrección: con más de una siembra, cada una necesita su fecha de siembra (distinta); no se puede dejar fuera de su
 * etapa una labor ya registrada que antes sí caía en ella.
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
        $finCampania = CampaniaSectorConsulta::fin($c);
        $registros = $this->consulta->registros($c);

        // Días de cada etapa y tandas de lo que la empieza (colocar infestadores, podar), con las labores del registro
        // diario y los hitos del módulo de cochinilla (infestaciones, cosechas: tienen historia más antigua)
        $dias = [];
        $inicios = [];
        $jornalesSiembra = [];
        foreach ($registros as $r) {
            $dias[$r['etapa']][$r['fecha']] = true;
            if (CampaniaSectorReglas::iniciaGrupo($r['etapa'], $r['nombre'])) {
                $inicios[$r['etapa']][$r['fecha']] = true;
            }
            if ($r['etapa'] === 'siembra') {
                $jornalesSiembra[$r['fecha']] = ($jornalesSiembra[$r['fecha']] ?? 0) + $r['trabajadores'];
            }
        }
        foreach ($this->consulta->eventosCochinilla($c) as $e) {
            $dias[$e['etapa']][$e['fecha']] = true;
            if ($e['inicia']) {
                $inicios[$e['etapa']][$e['fecha']] = true;
            }
        }

        // Rellenos: tandas grandes de siembra después de infestar o cosechar
        $primeraInfestacionOCosecha = collect(['infestacion', 'reinfestacion', 'cosecha_madres', 'cosecha'])
            ->flatMap(fn($e) => array_keys($dias[$e] ?? []))->min();
        $diasSiembra = array_keys($dias['siembra'] ?? []);
        $posteriores = $primeraInfestacionOCosecha ? array_values(array_filter($diasSiembra, fn($f) => $f > $primeraInfestacionOCosecha)) : [];
        $rellenos = [];
        foreach ($this->tandas($posteriores) as $tanda) {
            if (array_sum(array_map(fn($f) => $jornalesSiembra[$f] ?? 0, $tanda)) >= CampaniaSectorReglas::RELLENO_MIN_JORNALES) {
                $rellenos = array_merge($rellenos, $tanda);
            }
        }
        $registradas = Siembra::where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '<=', $finCampania)
            ->orderBy('fecha_siembra')->pluck('fecha_siembra')->map(fn($f) => Carbon::parse($f)->toDateString());
        // Solo las labores marcan un relleno: una siembra del registro durante la campaña suele ser una renovación
        $fechasRelleno = $rellenos;
        $base = $registradas->filter(fn($f) => $f <= $inicioCampania)->last()
            ?? collect($diasSiembra)->reject(fn($f) => in_array($f, $rellenos, true))->min();

        $fechasSiembra = array_merge([$base], array_map(fn($t) => $t[0], $this->tandas(array_values(array_unique($fechasRelleno)))));
        $fechasSiembra = array_slice($fechasSiembra, 0, max(1, min(CampaniaSectorReglas::MAX_GRUPOS, $hileras)));
        $n = count($fechasSiembra);
        $rangos = CampaniaSectorReglas::repartirHileras($hileras, $n);

        // Etapas de cada siembra
        $etapasPorSiembra = array_fill(0, $n, []);
        foreach (array_keys(CampaniaSectorReglas::ETAPAS) as $etapa) {
            if (empty($dias[$etapa])) {
                continue;
            }
            if ($n === 1) {
                $etapasPorSiembra[0][$etapa] = min(array_keys($dias[$etapa]));
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

        DB::transaction(function () use ($c, $rangos, $fechasSiembra, $etapasPorSiembra) {
            $c->sectores()->delete();
            foreach ($rangos as $k => [$desde, $hasta]) {
                $sector = CampaniaSector::create([
                    'campo_campania_id' => $c->id,
                    'nombre' => CampaniaSectorReglas::nombreSiembra($k + 1),
                    'fecha_siembra' => $fechasSiembra[$k] ?? null,
                    'hilera_desde' => $desde,
                    'hilera_hasta' => $hasta,
                    'origen' => 'detectado',
                ]);
                foreach ($etapasPorSiembra[$k] as $etapa => $fecha) {
                    $sector->etapas()->create(['etapa' => $etapa, 'fecha_desde' => $fecha]);
                }
            }
        });
        return count($rangos);
    }

    /** @return array{campanias:int, con_varios_grupos:int, respetadas:int} */
    public function detectarTodas(bool $forzar = false): array
    {
        $n = $varios = $respetadas = 0;
        CampoCampania::query()->whereNotNull('fecha_inicio')->orderBy('id')->chunkById(100, function ($campanias) use ($forzar, &$n, &$varios, &$respetadas) {
            foreach ($campanias as $c) {
                $grupos = $this->detectar($c, $forzar);
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
     */
    public function guardar(CampoCampania $c, int $hileras, array $sectores, ?string $motivo = null): void
    {
        $limpios = $this->validar($c, $hileras, $sectores);

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

        DB::transaction(function () use ($c, $hileras, $limpios, $antes, $motivo) {
            $campoAntes = (int) Campo::whereKey($c->campo)->value('hileras');
            if ($campoAntes !== $hileras) {
                Campo::whereKey($c->campo)->update(['hileras' => $hileras]);
            }
            $c->sectores()->delete();
            foreach ($limpios as $s) {
                $sector = CampaniaSector::create([
                    'campo_campania_id' => $c->id,
                    'nombre' => $s['nombre'],
                    'fecha_siembra' => $s['fecha_siembra'],
                    'hilera_desde' => $s['hilera_desde'],
                    'hilera_hasta' => $s['hilera_hasta'],
                    'origen' => 'manual',
                ]);
                foreach ($s['etapas'] as $e) {
                    $sector->etapas()->create($e);
                }
            }
            AuditoriaServicio::registrar(CampoCampania::class, $c->id, 'editar',
                ['hileras' => $campoAntes, 'siembras' => $this->resumen($antes)],
                ['hileras' => $hileras, 'siembras' => $this->resumen($limpios)],
                mb_substr("Mapa de etapas de {$c->nombre_campania} (campo {$c->campo})" . ($motivo ? ". {$motivo}" : ''), 0, 255));
        });
    }

    /**
     * Agrega una siembra (relleno) sembrada en $fecha: queda en siembra desde ese día y las hileras se reparten parejo
     * (se corrige en el mapa). Desde la tarjeta de la campaña o al registrar horas.
     */
    public function agregarSiembra(CampoCampania $c, string $fecha, ?string $motivo = null): void
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
        $this->guardar($c, $hileras, $sectores, $motivo ?? ('Nueva siembra (relleno) sembrada el ' . Carbon::parse($fecha)->format('d/m/Y')));
    }

    /** Las siembras con una más (relleno) en $fecha. Las anteriores sin fecha de siembra toman la del registro o el inicio. */
    public static function conSiembraNueva(array $sectores, string $fecha, int $hileras, CampoCampania $c): array
    {
        if (count($sectores) + 1 > $hileras) {
            throw new ErrorControlado("El campo {$c->campo} tiene {$hileras} hilera(s): no alcanzan para otra siembra. Aumenta las hileras en el mapa de etapas.");
        }
        $registro = Siembra::where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '<', $fecha)->max('fecha_siembra');
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
            $limpios[] = ['nombre' => mb_substr($nombre, 0, 40), 'fecha_siembra' => $fechaSiembra ?: null,
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
