<?php

namespace App\Services\Cuadrilla\Reemplazo;

use App\Exceptions\ErrorControlado;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral;
use App\Models\Cuadrillero;
use App\Services\Cuadrilla\TramoLaboralServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reemplazar un cuadrillero por otro en un tramo laboral (semana), p. ej. cuando se anotó a la persona equivocada.
 *
 * Todo lo del anterior en ese tramo pasa al nuevo, para que reportes, pagos y costos lo vean como del nuevo:
 * - su fila en cada grupo del tramo (cuad_tramo_cuadrilleros: id y nombre guardado);
 * - sus registros diarios (horas, detalle campo/labor y bonos van con el registro);
 * - su producción (kilos) en actividades de esos días;
 * - opcional, su riego de esos días (reg_resumen): el riego de un cuadrillero se paga con su jornal de cuadrilla, así
 *   que si se queda con el anterior, que ya no tiene registro, no se le paga a nadie;
 * - el nombre en la BDD de costos (resumen_costo_diarios) de esas filas: los montos no cambian (mismo grupo y jornal).
 *
 * No se puede si el anterior tiene días pagados en el tramo o si el nuevo ya está en el tramo. Queda en la auditoría.
 */
class CuadrillaReemplazoProceso
{
    /**
     * Lo que se movería al reemplazar (para mostrarlo antes de confirmar).
     *
     * @return array{registros:int, horas:float, detalle:int, bonos:int, produccion:int, riego:int, riego_dias:string[], pagados:int}
     */
    public function resumen(CuadTramoLaboral $tramo, int $anteriorId): array
    {
        [$inicio, $fin] = $this->rango($tramo);
        $registros = CuadRegistroDiario::where('tramo_laboral_id', $tramo->id)->where('cuadrillero_id', $anteriorId)->get();
        $ids = $registros->pluck('id');
        $riego = $this->riego($anteriorId, $inicio, $fin);

        return [
            'registros' => $registros->count(),
            'horas' => round((float) $registros->sum('total_horas'), 2),
            'detalle' => DB::table('cuad_detalles_horas')->whereIn('registro_diario_id', $ids)->count(),
            'bonos' => DB::table('cuad_bonos_actividades')->whereIn('registro_diario_id', $ids)->count(),
            'produccion' => $this->produccion($anteriorId, $inicio, $fin)->count(),
            'riego' => $riego->count(),
            'riego_dias' => $riego->pluck('fecha')->map(fn($f) => Carbon::parse($f)->format('d/m'))->unique()->values()->all(),
            'pagados' => TramoLaboralServicio::registrosPagados($registros)->count(),
        ];
    }

    public function ejecutar(CuadTramoLaboral $tramo, int $anteriorId, int $nuevoId, bool $moverRiego = true): array
    {
        if (!$nuevoId) {
            throw new ErrorControlado('Elige el cuadrillero nuevo.');
        }
        if ($anteriorId === $nuevoId) {
            throw new ErrorControlado('El cuadrillero nuevo es el mismo que el actual.');
        }
        $anterior = Cuadrillero::withTrashed()->findOrFail($anteriorId);
        $nuevo = Cuadrillero::find($nuevoId) ?? throw new ErrorControlado('El cuadrillero nuevo no existe o está eliminado.');

        $yaEsta = DB::table('cuad_tramo_cuadrilleros as tc')
            ->join('cuad_tramo_grupos as tg', 'tg.id', '=', 'tc.cuad_tramo_laboral_grupo_id')
            ->where('tg.cuad_tramo_laboral_id', $tramo->id)->where('tc.cuadrillero_id', $nuevoId)->exists();
        if ($yaEsta) {
            throw new ErrorControlado("{$nuevo->nombres} ya está en esta semana. Si es la misma persona anotada dos veces, pasa sus horas a una sola fila y quita la otra.");
        }
        [$inicio, $fin] = $this->rango($tramo);
        // Los tramos no se cruzan, pero un registro suelto del nuevo esos días (otro tramo) sería un doble pago
        $choque = CuadRegistroDiario::where('cuadrillero_id', $nuevoId)->whereBetween('fecha', [$inicio, $fin])->exists();
        if ($choque) {
            throw new ErrorControlado("{$nuevo->nombres} ya tiene registros de cuadrilla en estos días (en otra semana). Revísalo antes de reemplazar.");
        }

        $resumen = $this->resumen($tramo, $anteriorId);
        if ($resumen['pagados'] > 0) {
            throw new ErrorControlado("No se puede reemplazar: {$anterior->nombres} tiene {$resumen['pagados']} día(s) ya pagados en un desglose en esta semana.");
        }

        DB::transaction(function () use ($tramo, $anterior, $nuevo, $inicio, $fin, $moverRiego, $resumen) {
            $registros = CuadRegistroDiario::where('tramo_laboral_id', $tramo->id)->where('cuadrillero_id', $anterior->id);
            $idsRegistros = (clone $registros)->pluck('id');
            $idsDetalle = DB::table('cuad_detalles_horas')->whereIn('registro_diario_id', $idsRegistros)->pluck('id');
            $idsBonos = DB::table('cuad_bonos_actividades')->whereIn('registro_diario_id', $idsRegistros)->pluck('id');

            $registros->update(['cuadrillero_id' => $nuevo->id]);
            DB::table('cuad_tramo_cuadrilleros as tc')
                ->join('cuad_tramo_grupos as tg', 'tg.id', '=', 'tc.cuad_tramo_laboral_grupo_id')
                ->where('tg.cuad_tramo_laboral_id', $tramo->id)->where('tc.cuadrillero_id', $anterior->id)
                ->update(['tc.cuadrillero_id' => $nuevo->id, 'tc.nombres' => $nuevo->nombres, 'tc.updated_at' => now()]);
            DB::table('produccion_registros')->whereIn('id', $this->produccion($anterior->id, $inicio, $fin)->pluck('p.id'))
                ->update(['cuadrillero_id' => $nuevo->id, 'updated_at' => now()]);

            $idsRiego = collect();
            if ($moverRiego) {
                $consolidados = $this->riego($anterior->id, $inicio, $fin);
                DB::table('reg_resumen')->whereIn('id', $consolidados->pluck('id'))->update(['trabajador_id' => $nuevo->id]);
                $idsRiego = DB::table('reg_registro_diario')->whereIn('consolidado_id', $consolidados->pluck('id'))->pluck('id');
            }

            // BDD de costos: solo el nombre de esas filas (el costo no cambia: mismo grupo, mismo jornal). En origen
            // 'cuadrilla' el origen_id puede ser de detalle, bono o riego (tablas distintas): por eso se exige el nombre anterior.
            DB::table('resumen_costo_diarios')->where('trabajador', $anterior->nombres)->whereBetween('fecha', [$inicio, $fin])
                ->where(function ($q) use ($idsDetalle, $idsBonos, $idsRiego) {
                $q->where(fn($w) => $w->where('origen_tipo', 'cuadrilla')->whereIn('origen_id', $idsDetalle->merge($idsBonos)->merge($idsRiego)))
                    ->orWhere(fn($w) => $w->where('origen_tipo', 'cuadrilla_bono')->whereIn('origen_id', $idsBonos));
            })->update(['trabajador' => $nuevo->nombres]);

            AuditoriaServicio::registrar('CuadTramoLaboral', $tramo->id, 'editar',
                ['cuadrillero_id' => $anterior->id, 'cuadrillero' => $anterior->nombres],
                ['cuadrillero_id' => $nuevo->id, 'cuadrillero' => $nuevo->nombres] + $resumen + ['riego_movido' => $moverRiego],
                "Reemplazo de cuadrillero en la semana {$inicio} a {$fin}");
        });

        return $resumen;
    }

    private function rango(CuadTramoLaboral $tramo): array
    {
        return [Carbon::parse($tramo->fecha_inicio)->toDateString(), Carbon::parse($tramo->fecha_fin)->toDateString()];
    }

    private function produccion(int $cuadrilleroId, string $inicio, string $fin)
    {
        return DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
            ->where('p.cuadrillero_id', $cuadrilleroId)->whereBetween('a.fecha', [$inicio, $fin])->select('p.id');
    }

    private function riego(int $cuadrilleroId, string $inicio, string $fin)
    {
        return DB::table('reg_resumen')->where('trabajador_type', Cuadrillero::class)->where('trabajador_id', $cuadrilleroId)
            ->whereBetween('fecha', [$inicio, $fin])->get(['id', 'fecha']);
    }
}
