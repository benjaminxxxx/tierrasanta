<?php

namespace App\Services\Campania\Etapa;

use App\Models\CampoCampania;
use App\Models\EvalBrotesPorPiso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * En qué etapa está una campaña (por las fechas que ya tiene registradas) y qué evaluaciones de brotes le
 * tocarían según la configuración.
 */
class CampaniaEtapaConsulta
{
    /**
     * Línea de tiempo de una campaña: hitos con fecha (y los días desde el inicio), la etapa actual y las
     * evaluaciones de brotes sugeridas.
     *
     * @return array{inicio:?string, fin:string, total_dias:int, hoy_dia:?int, hitos:array, sugeridas:array, actual:?array}
     */
    public function lineaDeTiempo(CampoCampania $c): array
    {
        $inicio = $c->fecha_inicio ? Carbon::parse($c->fecha_inicio)->startOfDay() : null;
        $hoy = now()->startOfDay();
        $evaluaciones = EvalBrotesPorPiso::where('campania_id', $c->id)->orderBy('fecha')->pluck('fecha');

        $hitos = [];
        $agregar = function (string $etapa, $fecha, ?string $detalle = null) use (&$hitos, $inicio) {
            if (!$fecha) {
                return;
            }
            $f = Carbon::parse($fecha)->startOfDay();
            [$nombre, $color] = CampaniaEtapaReglas::ETAPAS[$etapa];
            $hitos[] = [
                'etapa' => $etapa,
                'nombre' => $nombre,
                'color' => $color,
                'fecha' => $f->toDateString(),
                'dia' => $inicio ? (int) $inicio->diffInDays($f, false) : null,
                'detalle' => $detalle,
            ];
        };

        $agregar('inicio', $c->fecha_inicio);
        $siembra = $c->fecha_siembra;
        if ($siembra && $inicio && Carbon::parse($siembra)->diffInDays($inicio) <= 365) {
            $agregar('siembra', $siembra);
        }
        $agregar('poblacion', $c->pp_dia_cero_fecha_evaluacion, 'Día cero');
        $agregar('poblacion', $c->pp_resiembra_fecha_evaluacion, 'Resiembra');
        foreach ($evaluaciones as $i => $fecha) {
            $agregar('brotes', $fecha, ($i + 1) . 'ª evaluación');
        }
        $agregar('infestacion', $c->infestacion_fecha);
        $agregar('reinfestacion', $c->reinfestacion_fecha);
        foreach (['eval_infest_fecha_primera' => '1ª', 'eval_infest_fecha_segunda' => '2ª', 'eval_infest_fecha_tercera' => '3ª'] as $campo => $n) {
            $agregar('eval_infestacion', $c->$campo, "{$n} evaluación");
        }
        $agregar('cosecha_madres', $c->cosechamadres_fecha_cosecha);
        $agregar('cosecha', $c->cosch_fecha);
        $agregar('cierre', $c->fecha_fin);

        // Orden del proceso; dentro de una etapa, por fecha
        $orden = array_flip(array_keys(CampaniaEtapaReglas::ETAPAS));
        usort($hitos, fn($a, $b) => [$orden[$a['etapa']], $a['fecha']] <=> [$orden[$b['etapa']], $b['fecha']]);

        // Etapa actual: la más avanzada del proceso que ya ocurrió
        $ocurridos = array_filter($hitos, fn($h) => $h['fecha'] <= $hoy->toDateString());
        $actual = $ocurridos ? end($ocurridos) : null;

        $finFechas = array_filter([$c->fecha_fin, $c->cosch_fecha, ...array_column($hitos, 'fecha')]);
        $fin = $c->fecha_fin ? Carbon::parse($c->fecha_fin) : Carbon::parse(max([...$finFechas, $hoy->toDateString()]));
        $totalDias = $inicio ? max(1, (int) $inicio->diffInDays($fin->startOfDay())) : 1;

        return [
            'inicio' => $inicio?->toDateString(),
            'fin' => $fin->toDateString(),
            'total_dias' => $totalDias,
            'hoy_dia' => $inicio && !$c->fecha_fin ? (int) $inicio->diffInDays($hoy, false) : null,
            'hitos' => $hitos,
            'sugeridas' => $this->sugeridas($c, $evaluaciones->count()),
            'actual' => $actual,
        ];
    }

    /**
     * Evaluaciones de brotes que según la configuración ya deberían existir y no están.
     *
     * - Sin infestación: la k-ésima se sugiere cuando pasaron sus días y la campaña tiene menos de k evaluaciones.
     * - Con infestación: ya pasó la etapa de evaluaciones; solo se avisa si no tiene ninguna (para registrarla si existe).
     *
     * @return array<int, array{numero:int, dias:int, fecha:string, vencida:bool}>
     */
    public function sugeridas(CampoCampania $c, ?int $registradas = null): array
    {
        if (!$c->fecha_inicio || $c->fecha_fin || $c->infestacion_fecha) {
            return [];
        }
        $registradas ??= EvalBrotesPorPiso::where('campania_id', $c->id)->count();
        $inicio = Carbon::parse($c->fecha_inicio)->startOfDay();
        $transcurridos = (int) $inicio->diffInDays(now()->startOfDay(), false);

        $lista = [];
        foreach (CampaniaEtapaReglas::diasEvaluacionBrotes() as $i => $dias) {
            $numero = $i + 1;
            if ($numero <= $registradas) {
                continue;
            }
            $lista[] = [
                'numero' => $numero,
                'dias' => $dias,
                'fecha' => $inicio->copy()->addDays($dias)->toDateString(),
                'vencida' => $transcurridos >= $dias,
            ];
        }
        return $lista;
    }

    /**
     * Campañas abiertas que necesitan evaluación de brotes, para tareas pendientes.
     *
     * @return array{por_evaluar: Collection, infestadas_sin_evaluar: Collection}
     */
    public function pendientes(): array
    {
        $dias = CampaniaEtapaReglas::diasEvaluacionBrotes();
        $tope = now()->subDays(CampaniaEtapaReglas::diasMaximoSugerencia())->toDateString();
        $conteo = EvalBrotesPorPiso::selectRaw('campania_id, COUNT(*) n')->groupBy('campania_id')->pluck('n', 'campania_id');

        $abiertas = CampoCampania::whereNull('fecha_fin')->whereNotNull('fecha_inicio')->where('fecha_inicio', '>=', $tope)
            ->orderBy('campo')->get();

        $porEvaluar = $dias ? $abiertas->whereNull('infestacion_fecha')->map(function ($c) use ($conteo) {
            $vencidas = array_values(array_filter($this->sugeridas($c, (int) ($conteo[$c->id] ?? 0)), fn($s) => $s['vencida']));
            return $vencidas ? ['campania' => $c, 'registradas' => (int) ($conteo[$c->id] ?? 0), 'siguiente' => $vencidas[0], 'faltan' => count($vencidas)] : null;
        })->filter()->values() : collect();

        $infestadasSinEvaluar = $abiertas->whereNotNull('infestacion_fecha')->filter(fn($c) => !isset($conteo[$c->id]))->values();

        return ['por_evaluar' => $porEvaluar, 'infestadas_sin_evaluar' => $infestadasSinEvaluar];
    }

    /** Etapa actual de varias campañas a la vez (para el resumen), sin consultar campaña por campaña. */
    public function etapasActuales(Collection $campanias): array
    {
        $conBrotes = EvalBrotesPorPiso::whereIn('campania_id', $campanias->pluck('id'))->distinct()->pluck('campania_id')->flip();
        $hoy = now()->toDateString();
        $resultado = [];
        foreach ($campanias as $c) {
            $fechas = [
                'cierre' => $c->fecha_fin,
                'cosecha' => $c->cosch_fecha,
                'cosecha_madres' => $c->cosechamadres_fecha_cosecha,
                'eval_infestacion' => $c->eval_infest_fecha_tercera ?: $c->eval_infest_fecha_segunda ?: $c->eval_infest_fecha_primera,
                'reinfestacion' => $c->reinfestacion_fecha,
                'infestacion' => $c->infestacion_fecha,
                'brotes' => isset($conBrotes[$c->id]) ? ($c->brotexpiso_fecha_evaluacion ?: $c->fecha_inicio) : null,
                'poblacion' => $c->pp_resiembra_fecha_evaluacion ?: $c->pp_dia_cero_fecha_evaluacion,
                'inicio' => $c->fecha_inicio,
            ];
            $resultado[$c->id] = null;
            foreach ($fechas as $etapa => $fecha) {
                if ($fecha && Carbon::parse($fecha)->toDateString() <= $hoy) {
                    [$nombre, $color] = CampaniaEtapaReglas::ETAPAS[$etapa];
                    $resultado[$c->id] = ['etapa' => $etapa, 'nombre' => $nombre, 'color' => $color];
                    break;
                }
            }
        }
        return $resultado;
    }
}
