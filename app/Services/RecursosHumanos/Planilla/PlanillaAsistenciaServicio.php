<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanGrupo;
use App\Services\Handsontable\HSTPlanillaAsistencia;

class PlanillaAsistenciaServicio
{
    public function obtenerHorasCompleto($mes, $anio)
    {
        // Obtener empleados con sus horas diarias
        
        $empleados = app(HSTPlanillaAsistencia::class)->obtenerAsistenciaMensualAgraria($mes, $anio);

        // Obtener información adicional de asistencia
        $informacionAsistenciaAdicional = app(HSTPlanillaAsistencia::class)->obtenerInformacionAsistenciaAdicional($mes, $anio);

        // Obtener colores de grupos
        $grupoColores = PlanGrupo::get()->pluck("color", "codigo")->toArray();

        // Enriquecer los datos de empleados con el color del grupo
        $empleadosEnriquecidos = collect($empleados)->map(function ($empleado, $indice) use ($grupoColores) {
            $grupoColor = isset($empleado['grupo']) && isset($grupoColores[$empleado['grupo']])
                ? $grupoColores[$empleado['grupo']]
                : '#ffffff';

            return array_merge([
                'orden' => $indice + 1,
                'empleado_grupo_color' => $grupoColor,
            ], $empleado);
        })->toArray();

        return [
            'empleados' => $empleadosEnriquecidos,
            'informacionAsistenciaAdicional' => $informacionAsistenciaAdicional
        ];
    }
}