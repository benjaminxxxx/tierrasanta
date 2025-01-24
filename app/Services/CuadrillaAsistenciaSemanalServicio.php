<?php

namespace App\Services;

use App\Models\CuaAsistenciaSemanal as CuadrillaAsistenciaSemanal;
use Exception;

class CuadrillaAsistenciaSemanalServicio
{
    /**
     * Elimina todos los registros de la semana, incluyendo las actividades dentro de su rango de fechas
     * @param int $cuadrillaAsistenciaSemanalId de la Semana
     */
    public static function eliminarSemana($cuadrillaAsistenciaSemanalId)
    {
        $cuadrillaAsistenciaSemanal = CuadrillaAsistenciaSemanal::find($cuadrillaAsistenciaSemanalId);
        if(!$cuadrillaAsistenciaSemanal){
            throw new Exception("La semana no existe");
        }
        
        $cuadrillaAsistenciaSemanal->actividades->delete();
        $cuadrillaAsistenciaSemanal->delete();   
    }
}
