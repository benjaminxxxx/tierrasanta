<?php

namespace App\Services\Evaluacion\Siembra;

use App\Exceptions\ErrorControlado;
use App\Models\CampoCampania;
use App\Models\EvalBrotesPorPiso;
use App\Models\EvalPoblacionPlanta;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use Illuminate\Support\Carbon;

/**
 * Siembras de una campaña vistas desde las evaluaciones y qué evaluaciones tiene cada una.
 * Una campaña cerrada conserva sus evaluaciones solo como dato: no se crean, editan, mueven ni eliminan.
 */
class EvaluacionSiembraConsulta
{
    public static function exigirAbierta(CampoCampania $c): void
    {
        if ($c->fecha_fin) {
            throw new ErrorControlado("La campaña {$c->nombre_campania} (campo {$c->campo}) está cerrada desde el "
                . Carbon::parse($c->fecha_fin)->format('d/m/Y') . ': sus evaluaciones ya no se pueden registrar, modificar, mover ni eliminar.');
        }
    }

    /** @return array<int, array{id:int, nombre:string, fecha_siembra:?string, hilera_desde:int, hilera_hasta:int, area:?float, etiqueta:string, poblacion:int, brotes:int}> */
    public function siembras(CampoCampania $c): array
    {
        $sectores = $c->sectores()->get();
        $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $areaCampo = (float) ($c->campo_model?->area ?? 0);
        $poblacion = EvalPoblacionPlanta::where('campania_id', $c->id)->selectRaw('campania_sector_id, COUNT(*) n')->groupBy('campania_sector_id')->pluck('n', 'campania_sector_id');
        $brotes = EvalBrotesPorPiso::where('campania_id', $c->id)->selectRaw('campania_sector_id, COUNT(*) n')->groupBy('campania_sector_id')->pluck('n', 'campania_sector_id');

        return $sectores->map(function ($s) use ($hileras, $areaCampo, $poblacion, $brotes, $sectores) {
            $n = $s->hilera_hasta - $s->hilera_desde + 1;
            $fecha = $s->fecha_siembra?->format('d/m/Y');
            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'fecha_siembra' => $s->fecha_siembra?->toDateString(),
                'hilera_desde' => $s->hilera_desde,
                'hilera_hasta' => $s->hilera_hasta,
                // Con una sola siembra, el área es la del campo; con varias, la parte de sus hileras
                'area' => $areaCampo > 0 ? round($sectores->count() === 1 ? $areaCampo : $areaCampo * $n / $hileras, 4) : null,
                'etiqueta' => $s->nombre . ($fecha ? " · sembrada el {$fecha}" : '') . " · hileras {$s->hilera_desde}–{$s->hilera_hasta}",
                'poblacion' => (int) ($poblacion[$s->id] ?? 0),
                'brotes' => (int) ($brotes[$s->id] ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Evaluaciones de la campaña (de esas siembras, si se indican), para el aviso al quitar una siembra.
     *
     * @param int[]|null $sectores
     * @return array<int, array{clave:string, tipo:string, nombre_tipo:string, id:int, sector_id:?int, siembra:string, fecha:string, evaluador:?string, camas:int}>
     */
    public function evaluaciones(CampoCampania $c, ?array $sectores = null): array
    {
        if ($sectores === []) {
            return [];
        }
        $nombres = $c->sectores()->pluck('nombre', 'id');
        $fila = fn(string $tipo, string $nombreTipo, $e, $fecha) => [
            'clave' => "{$tipo}-{$e->id}",
            'tipo' => $tipo,
            'nombre_tipo' => $nombreTipo,
            'id' => $e->id,
            'sector_id' => $e->campania_sector_id,
            'siembra' => $nombres[$e->campania_sector_id] ?? 'Sin siembra',
            'fecha' => Carbon::parse($fecha)->toDateString(),
            'evaluador' => $e->evaluador,
            'camas' => $e->detalles_count,
        ];
        $filtro = fn($q) => $sectores !== null ? $q->whereIn('campania_sector_id', $sectores) : $q;

        $poblacion = $filtro(EvalPoblacionPlanta::where('campania_id', $c->id))->withCount('detalles')->get()
            ->map(fn($e) => $fila('poblacion', 'Población', $e, $e->fecha_eval_cero));
        $brotes = $filtro(EvalBrotesPorPiso::where('campania_id', $c->id))->withCount('detalles')->get()
            ->map(fn($e) => $fila('brotes', 'Brotes', $e, $e->fecha));

        return $poblacion->concat($brotes)->sortBy('fecha')->values()->all();
    }

    /**
     * A qué siembra corresponde por fecha una evaluación: la sembrada más recientemente hasta ese día.
     *
     * @param array $siembras filas con fecha_siembra
     * @return array|null la siembra sugerida
     */
    public static function sugerida(string $fecha, array $siembras): ?array
    {
        return collect($siembras)->filter(fn($s) => !empty($s['fecha_siembra']) && $s['fecha_siembra'] <= $fecha)
            ->sortBy('fecha_siembra')->last();
    }
}
