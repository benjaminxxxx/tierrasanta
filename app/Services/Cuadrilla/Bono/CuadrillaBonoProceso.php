<?php

namespace App\Services\Cuadrilla\Bono;

use Illuminate\Support\Facades\DB;

/**
 * Escrituras ligadas a los métodos de bono que los triggers no cubren: si solo cambia el flag "respeta el jornal"
 * de un método (y el bono no cambia), la fila del bono no se actualiza y horas_destajo quedaría con el valor antiguo.
 */
class CuadrillaBonoProceso
{
    /** Recalcula horas_destajo de los registros diarios de cuadrilla con bono en esta actividad (mismo criterio que los triggers). */
    public static function recalcularHorasDestajo(int $actividadId): void
    {
        $registros = DB::table('cuad_bonos_actividades')->where('actividad_id', $actividadId)->pluck('registro_diario_id')->unique();
        foreach ($registros as $id) {
            $horas = (float) DB::table('cuad_bonos_actividades as ba')
                ->join('actividad_metodos as am', 'am.id', '=', 'ba.metodo_id')
                ->join('actividades as a', 'a.id', '=', 'ba.actividad_id')
                ->join('cuad_detalles_horas as dh', fn($j) => $j->on('dh.registro_diario_id', '=', 'ba.registro_diario_id')->on('dh.codigo_labor', '=', 'a.codigo_labor'))
                ->where('ba.registro_diario_id', $id)->whereNull('am.estandar')->where('am.respeta_jornal', false)
                ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0'));
            DB::table('cuad_registros_diarios')->where('id', $id)->update(['horas_destajo' => round($horas, 2)]);
        }
    }
}
