<?php

namespace App\Services\Campania\Cobertura;

use App\Models\Actividad;
use App\Models\CampoCampania;
use Illuminate\Support\Carbon;

/**
 * Cobertura de actividades por campañas (solo lectura).
 *
 * Toda labor registrada en un campo (tabla `actividades`) debe caer dentro de alguna campaña de ese campo: sus
 * costos se asignan a la campaña por fecha. Entre el cierre de una campaña y el inicio de la siguiente puede
 * haber días sin campaña, pero solo si esos días no tienen actividades.
 *
 * Solo se revisan campos que tienen campañas (los demás no llevan costo por campaña).
 */
class CampaniaCoberturaConsulta
{
    /**
     * Huecos sin campaña que quedarían alrededor de un rango propuesto, con sus actividades.
     * - antes: entre el cierre de la campaña anterior y el inicio propuesto.
     * - despues: entre el cierre propuesto y el inicio de la campaña siguiente (solo si se indica cierre).
     *
     * @return array{antes: ?array, despues: ?array} cada hueco: [desde, hasta, vecina (CampoCampania), actividades]
     */
    public function huecos(string $campo, string $inicio, ?string $fin, ?int $excluirId = null): array
    {
        $inicio = Carbon::parse($inicio)->toDateString();
        $fin = $fin ? Carbon::parse($fin)->toDateString() : null;

        $vecinas = CampoCampania::where('campo', $campo)
            ->when($excluirId, fn($q) => $q->where('id', '!=', $excluirId))
            ->orderBy('fecha_inicio')
            ->get();

        $anterior = $vecinas->filter(fn($c) => $c->fecha_inicio->toDateString() < $inicio)->last();
        $siguiente = $vecinas->first(fn($c) => $c->fecha_inicio->toDateString() > $inicio);

        $antes = null;
        if ($anterior?->fecha_fin) {
            $desde = $anterior->fecha_fin->copy()->addDay()->toDateString();
            $hasta = Carbon::parse($inicio)->subDay()->toDateString();
            if ($desde <= $hasta) {
                $antes = $this->hueco($campo, $desde, $hasta, $anterior);
            }
        }

        $despues = null;
        if ($fin && $siguiente) {
            $desde = Carbon::parse($fin)->addDay()->toDateString();
            $hasta = $siguiente->fecha_inicio->copy()->subDay()->toDateString();
            if ($desde <= $hasta) {
                $despues = $this->hueco($campo, $desde, $hasta, $siguiente);
            }
        }

        return ['antes' => $antes, 'despues' => $despues];
    }

    /**
     * Actividades del rango en días que no caen en ninguna campaña de su campo.
     *
     * @return array<string, array<string, string[]>> campo => [fecha => labores]
     */
    public function sinCampania(string $desde, string $hasta): array
    {
        $campos = CampoCampania::distinct()->pluck('campo');
        $campanias = CampoCampania::whereIn('campo', $campos)
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $desde))
            ->get(['campo', 'fecha_inicio', 'fecha_fin'])
            ->groupBy('campo');

        $resultado = [];
        $actividades = Actividad::whereIn('campo', $campos)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('campo')->orderBy('fecha')
            ->get(['campo', 'fecha', 'nombre_labor', 'codigo_labor']);

        foreach ($actividades as $actividad) {
            $dia = Carbon::parse($actividad->fecha)->toDateString();
            $cubierta = ($campanias[$actividad->campo] ?? collect())->contains(fn($c) =>
                $c->fecha_inicio->toDateString() <= $dia && ($c->fecha_fin === null || $c->fecha_fin->toDateString() >= $dia));
            if (!$cubierta) {
                $resultado[$actividad->campo][$dia][] = $actividad->nombre_labor ?: "Labor {$actividad->codigo_labor}";
            }
        }

        return array_map(fn($dias) => array_map(fn($l) => array_values(array_unique($l)), $dias), $resultado);
    }

    /** @return array<string, string[]> fecha => labores */
    public function actividadesEnRango(string $campo, string $desde, string $hasta): array
    {
        return Actividad::where('campo', $campo)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->get(['fecha', 'nombre_labor', 'codigo_labor'])
            ->groupBy(fn($a) => Carbon::parse($a->fecha)->toDateString())
            ->map(fn($g) => $g->map(fn($a) => $a->nombre_labor ?: "Labor {$a->codigo_labor}")->unique()->values()->all())
            ->all();
    }

    private function hueco(string $campo, string $desde, string $hasta, CampoCampania $vecina): array
    {
        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'vecina' => $vecina,
            'actividades' => $this->actividadesEnRango($campo, $desde, $hasta),
        ];
    }
}
