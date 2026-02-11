<?php

namespace App\Services\Planilla;

use App\Models\PlanConceptosConfig;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use App\Services\PlanSueldoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaMensualDetalleServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerarPlanillaMensualProceso
{
    public function ejecutar($data, $mes, $anio)
    {
        //Primero que nada salvamos los datos editables
        $this->guardarDatos($data, $mes, $anio);

        //siguiente paso
    }
    /**
     * Guarda los datos editables provenientes de Handsontable.
     * Cada registro se insertará o actualizará según si trae id o no.
     */
    private function guardarDatos(array $data, $mes, $anio): void
    {

        DB::transaction(function () use ($data, $mes, $anio) {

            $totalHorasMap = app(PlanillaRegistroDiarioServicio::class)->obtenerTotalHorasPorMes($mes, $anio);
            $sueldosPactados = app(PlanSueldoServicio::class)->obtenerSueldosPorMes($mes, $anio);
            $remuneracion_basica = $this->calcularRemuneracionBasica($mes, $anio);
            $asignacionesFamiliares = PlanillaEmpleadoServicio::obtenerAsignacionesFamiliares($mes, $anio);

            foreach ($data as $dato) {

                $empleadoId = $dato['plan_empleado_id'];
                if (!array_key_exists($dato['plan_empleado_id'], $totalHorasMap)) {
                    throw new Exception("Error por falta de indice");
                }
                $detalleHoras = $totalHorasMap[$dato['plan_empleado_id']];

                $info = [
                    'remuneracion_basica' => $remuneracion_basica,
                    'bonificacion' => (float) ($dato['bonificacion'] ?? 0),
                    'negro_bono_asistencia' => (float) ($dato['negro_bono_asistencia'] ?? 0),
                    'negro_bono_productividad' => $detalleHoras['total_bono_productividad'],
                    'dias_trabajados' => $detalleHoras['dias_trabajados'],
                    'horas_trabajadas' => $detalleHoras['horas_trabajadas'],
                    'negro_sueldo_bruto' => $sueldosPactados[$empleadoId],
                    'asignacion_familiar' => $asignacionesFamiliares[$empleadoId]??null,
                ];
                PlanillaMensualDetalleServicio::guardar($info, $dato['id']);
            }
        });
    }
    public static function calcularRemuneracionBasica(int $mes, int $anio): float
    {
        // 1. Obtener la RMV vigente
        $rmv = ConfiguracionHistorialServicio::valorVigente('rmv', $mes, $anio);

        // 2. Calcular la cantidad de días del mes
        $fecha = \Carbon\Carbon::createFromDate($anio, $mes, 1);
        $diasDelMes = $fecha->daysInMonth;

        // 3. Fórmula vigente (RMV / 30 × días trabajables del mes)
        $remuneracion = ($rmv / 30) * $diasDelMes;

        // 4. Devolver número con precisión
        return round($remuneracion, 2);
    }

}