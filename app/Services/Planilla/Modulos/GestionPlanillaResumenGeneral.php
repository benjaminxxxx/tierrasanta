<?php
namespace App\Services\Planilla\Modulos;
use App\Services\Planilla\Asistencia\ResumenAsistenciaPlanillaServicio;
use App\Services\Reporte\RptRecursosHumanosAsistenciasGeneral;

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
