<?php

namespace App\Services\Evaluacion\Siembra;

use App\Exceptions\ErrorControlado;
use App\Models\CampaniaSector;
use App\Models\CampoCampania;
use App\Models\EvalBrotesPorPiso;
use App\Models\EvalPoblacionPlanta;
use App\Services\Campania\CampaniaServicio;
use App\Services\Campania\Sector\CampaniaSectorProceso;
use App\Services\Evaluacion\BrotesPorPisoServicio;
use App\Services\Evaluacion\PoblacionPlantaServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Vincula las evaluaciones de población y brotes con su siembra: pasa las antiguas a la 1ª siembra, agrega una siembra
 * desde la evaluación, corrige una evaluación puesta en otra siembra y aplica lo decidido al quitar una siembra.
 */
class EvaluacionSiembraProceso
{
    /**
     * Pasa a la 1ª siembra las evaluaciones de la campaña que aún no tienen. No toca sus datos, fechas de edición ni
     * métricas (vale también para campañas cerradas).
     *
     * @return array{poblacion:int, brotes:int}
     */
    public function vincularCampania(CampoCampania $c, bool $simular = false): array
    {
        $poblacion = DB::table('eval_poblacion_plantas')->where('campania_id', $c->id)->whereNull('campania_sector_id');
        $brotes = DB::table('eval_brotes_por_pisos')->where('campania_id', $c->id)->whereNull('campania_sector_id');
        $conteo = ['poblacion' => (clone $poblacion)->count(), 'brotes' => (clone $brotes)->count()];
        if ($simular || array_sum($conteo) === 0) {
            return $conteo;
        }
        if (!$c->sectores()->exists() && $c->fecha_inicio) {
            app(CampaniaSectorProceso::class)->detectar($c);
        }
        $primera = $c->sectores()->value('id');
        if (!$primera) {
            return ['poblacion' => 0, 'brotes' => 0];
        }
        DB::transaction(function () use ($poblacion, $brotes, $primera) {
            $poblacion->update(['campania_sector_id' => $primera]);
            $brotes->update(['campania_sector_id' => $primera]);
        });
        return $conteo;
    }

    /** @return array{campanias:int, poblacion:int, brotes:int, sin_siembra:int} */
    public function vincularTodas(bool $simular = false): array
    {
        $ids = DB::table('eval_poblacion_plantas')->whereNull('campania_sector_id')->pluck('campania_id')
            ->concat(DB::table('eval_brotes_por_pisos')->whereNull('campania_sector_id')->pluck('campania_id'))->unique();
        $total = ['campanias' => 0, 'poblacion' => 0, 'brotes' => 0, 'sin_siembra' => 0];
        foreach (CampoCampania::whereIn('id', $ids)->orderBy('id')->get() as $c) {
            $r = $this->vincularCampania($c, $simular);
            if (array_sum($r) === 0) {
                $total['sin_siembra']++;
                continue;
            }
            $total['campanias']++;
            $total['poblacion'] += $r['poblacion'];
            $total['brotes'] += $r['brotes'];
        }
        return $total;
    }

    /** Agrega una siembra desde la evaluación. Devuelve su id. */
    public function agregarSiembra(CampoCampania $c, ?string $fecha, ?string $motivo): int
    {
        EvaluacionSiembraConsulta::exigirAbierta($c);
        $antes = $c->sectores()->pluck('id')->all();
        app(CampaniaSectorProceso::class)->agregarSiembra($c, (string) $fecha, $motivo, 'evaluacion');
        return (int) $c->sectores()->whereNotIn('id', $antes)->value('id');
    }

    /** Corrige la siembra de una evaluación. */
    public function mover(string $tipo, int $id, int $sectorDestino): void
    {
        DB::transaction(function () use ($tipo, $id, $sectorDestino) {
            $e = $this->evaluacion($tipo, $id);
            $c = CampoCampania::findOrFail($e->campania_id);
            EvaluacionSiembraConsulta::exigirAbierta($c);
            $this->moverSinRecalcular($e, $tipo, $c, $sectorDestino);
            $this->recalcular($c);
        });
    }

    /**
     * Lo decidido al quitar una siembra.
     *
     * @param array<string, array{accion:string, destino?:string}> $decisiones clave => eliminar | mover (destino = clave de la siembra en el mapa)
     * @param array<string, int> $sectorPorClave clave de la siembra en el mapa => id de la siembra que queda
     */
    public function aplicarDecisiones(CampoCampania $c, array $evaluaciones, array $decisiones, array $sectorPorClave): void
    {
        EvaluacionSiembraConsulta::exigirAbierta($c);
        foreach ($evaluaciones as $fila) {
            $d = $decisiones[$fila['clave']];
            if ($d['accion'] === 'eliminar') {
                $fila['tipo'] === 'poblacion'
                    ? app(PoblacionPlantaServicio::class)->eliminar($fila['id'])
                    : app(BrotesPorPisoServicio::class)->eliminar($fila['id']);
                continue;
            }
            $destino = $sectorPorClave[(string) ($d['destino'] ?? '')] ?? null;
            if (!$destino) {
                throw new ErrorControlado("Elige a qué siembra pasa la evaluación de {$fila['nombre_tipo']} del " . Carbon::parse($fila['fecha'])->format('d/m/Y') . '.');
            }
            $this->moverSinRecalcular($this->evaluacion($fila['tipo'], $fila['id']), $fila['tipo'], $c, $destino);
        }
        $this->recalcular($c);
    }

    public function recalcular(CampoCampania $c): void
    {
        app(CampaniaServicio::class)->actualizarMetricasPoblacion($c->id);
        app(BrotesPorPisoServicio::class)->recalcularMetricasCampania($c->id);
    }

    private function moverSinRecalcular(EvalPoblacionPlanta|EvalBrotesPorPiso $e, string $tipo, CampoCampania $c, int $sectorDestino): void
    {
        $destino = CampaniaSector::where('campo_campania_id', $c->id)->find($sectorDestino);
        if (!$destino) {
            throw new ErrorControlado('La siembra elegida no es de esta campaña.');
        }
        if ((int) $e->campania_sector_id === $destino->id) {
            return;
        }
        if ($tipo === 'poblacion') {
            if (EvalPoblacionPlanta::where('campania_sector_id', $destino->id)->exists()) {
                throw new ErrorControlado("{$destino->nombre} ya tiene su evaluación de población: elimina una de las dos o elige otra siembra.");
            }
        } elseif (EvalBrotesPorPiso::where('campania_sector_id', $destino->id)->whereDate('fecha', $e->fecha)->exists()) {
            throw new ErrorControlado("{$destino->nombre} ya tiene una evaluación de brotes del " . Carbon::parse($e->fecha)->format('d/m/Y') . '.');
        }
        $anterior = CampaniaSector::whereKey($e->campania_sector_id)->value('nombre') ?? 'Sin siembra';
        $e->update(['campania_sector_id' => $destino->id] + ($tipo === 'brotes' ? ['actualizado_por' => auth()->id()] : []));
        AuditoriaServicio::registrar($e::class, $e->id, 'editar', ['siembra' => $anterior], ['siembra' => $destino->nombre],
            "Evaluación de {$tipo} pasada de {$anterior} a {$destino->nombre} · {$c->campo} / {$c->nombre_campania}");
    }

    private function evaluacion(string $tipo, int $id): EvalPoblacionPlanta|EvalBrotesPorPiso
    {
        return $tipo === 'poblacion' ? EvalPoblacionPlanta::findOrFail($id) : EvalBrotesPorPiso::findOrFail($id);
    }
}
