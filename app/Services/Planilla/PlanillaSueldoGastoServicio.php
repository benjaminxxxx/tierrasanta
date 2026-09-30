<?php

namespace App\Services\Planilla;

use App\Models\PlanEmpleado;
use App\Models\PlanRegistroDiario;
use App\Models\PlanSueldo;
use App\Services\Reporte\RptPlanillaGeneral;
use Carbon\Carbon;
use Exception;

class PlanillaSueldoGastoServicio
{
    public function descargarPlanillaActualizada(){
         return app(RptPlanillaGeneral::class)->descargarPlanillaActualizada();
    }
    public function guardarSueldosMasivos($cambios, $mesVigencia, $anioVigencia)
    {
        
        if (empty($cambios)) {
            throw new Exception('No se proporcionaron cambios para procesar.');
        }

        if (!$mesVigencia || !$anioVigencia) {
            throw new Exception('Debe seleccionar el mes y el año de vigencia.');
        }
        $fechaInicio = Carbon::create($anioVigencia, $mesVigencia, 1)->startOfDay();

        // 🔍 Obtener todos los IDs de empleados que vienen en los cambios
        $empleadoIds = collect($cambios)->pluck('empleado_id')->toArray();

        // ⚠️ Validar que ninguno tenga un sueldo con fecha >= $fechaInicio
        $conflictos = PlanSueldo::whereIn('plan_empleado_id', $empleadoIds)
            ->where('fecha_inicio', '>=', $fechaInicio)
            ->pluck('plan_empleado_id')
            ->unique()
            ->toArray();

        if (!empty($conflictos)) {
            $nombres = PlanEmpleado::whereIn('id', $conflictos)
                ->pluck('nombres')
                ->implode(', ');

            throw new Exception("Los siguientes empleados ya tienen sueldos vigentes desde {$fechaInicio->format('d/m/Y')}: {$nombres}");
        }

        foreach ($cambios as $cambio) {
       
            $empleado = PlanEmpleado::findOrFail($cambio['empleado_id']);
            $nuevoSueldo = $cambio['nuevo_sueldo'];
            $ultimoSueldo = $empleado->ultimoSueldo;

            if ($ultimoSueldo) {
                $this->_finalizarSueldo($ultimoSueldo, $fechaInicio);
            }

            PlanSueldo::create([
                'plan_empleado_id'   => $empleado->id,
                'sueldo'        => $nuevoSueldo,
                'fecha_inicio'  => $fechaInicio,
            ]);
        }
    }
    private function _finalizarSueldo($ultimoSueldo, Carbon $fechaInicioSueldo): void
    {
        $ultimoSueldo->update([
            'fecha_fin' => $fechaInicioSueldo->copy()->subDay()->format('Y-m-d')
        ]);
    }
    public static function obtenerBonosPlanilla($anio, $mes)
    {
        $reporteDiario = PlanRegistroDiario::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        $registros = [];
        foreach ($reporteDiario as $reporte) {
            $registros[$reporte->detalleMensual->documento]['dia_' . Carbon::parse($reporte->fecha)->format('d')] = $reporte->total_bono;
        }
        return $registros;
    }

}
