<?php
namespace App\Services\Modulos\Planilla;
use App\Services\RecursosHumanos\Asistencias\Planilla\ResumenAsistenciaPlanillaServicio;
use App\Services\Reportes\RptRecursosHumanosAsistenciasGeneral;

class GestionPlanillaResumenGeneral
{
    public function descargarInforme(array $registros, $fechaInicio, $fechaFin, $grupoSeleccionado, $filtroNombres)
    {
        return app(RptRecursosHumanosAsistenciasGeneral::class)
            ->descargarInforme($registros,$fechaInicio, $fechaFin, $grupoSeleccionado, $filtroNombres);
    }

    public function obtenerDataResumen($fechaInicio, $fechaFin, $grupo, $nombres)
    {
        return app(ResumenAsistenciaPlanillaServicio::class)
            ->obtenerResumen($fechaInicio, $fechaFin, $grupo, $nombres);
    }
}
