<?php

namespace App\Services\Planilla;

use App\Models\PlanConceptosConfig;
use App\Models\PlanMensual;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use App\Services\PlanillaMensualServicio;
use App\Services\PlanSueldoServicio;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaDescuentoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaMensualDetalleServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Support\CalculoHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerarPlanillaMensualProceso
{
    public function ejecutar($data, $mes, $anio)
    {
        DB::transaction(function () use ($data, $mes, $anio) {
            PlanillaMensualServicio::guardarConfiguracionDesdeParametros($mes, $anio);
            self::guardarDatos($data, $mes, $anio);
            //Generar Excel mas adelante
        });
    }
    private function guardarDatos(array $data, $mes, $anio): void
    {

        $totalHorasMap = app(PlanillaRegistroDiarioServicio::class)->obtenerTotalHorasPorMes($mes, $anio);
        $sueldosPactados = app(PlanSueldoServicio::class)->obtenerSueldosPorMes($mes, $anio);
        $contratos = ContratoServicio::obtenerContratosVigentes($mes, $anio)->toArray();
        $descuentoAgrupados = PlanillaDescuentoServicio::obtenerDescuentos($mes, $anio);
        $empleados = PlanillaEmpleadoServicio::datosPlanilla($mes, $anio);
        $diasSuspendidos = PlanillaSuspensionServicio::obtenerSuspensiones($mes, $anio);
        $totalDiasMes = Carbon::create($anio, $mes, 1)->daysInMonth;

        foreach ($data as $dato) {

            $empleadoId = $dato['plan_empleado_id'];
            $this->validarExistenciaIndices($empleadoId, $dato['nombres'], $totalHorasMap, $contratos, $empleados);

            $empleado = $empleados[$empleadoId];
            $contrato = $contratos[$empleadoId];
            $detalleHoras = $totalHorasMap[$empleadoId];
            $edad = $empleado['edad_contable'];//$empleados[];

            if (!$edad) {
                throw new Exception("El empleado {$dato['nombres']} no tiene fecha de nacimiento registrado");
            }
            $descuento = PlanillaDescuentoServicio::calcularDescuentoEmpleado(
                $edad,
                $contrato['plan_sp_codigo'],
                $contrato['esta_jubilado'],
                $descuentoAgrupados
            );

            $diasNoLaborados = $diasSuspendidos[$empleadoId] ?? 0;
            $diasLaborados = $totalDiasMes - $diasNoLaborados;

            $info = [
                'dias_laborados' => $diasLaborados,
                'dias_no_laborados' => $diasNoLaborados,
                'negro_bono_asistencia' => (float) ($dato['negro_bono_asistencia'] ?? 0),
                'negro_bono_productividad' => $detalleHoras['total_bono_productividad'],
                'dias_trabajados' => $detalleHoras['dias_trabajados'],
                'faltas_injustificadas' => $detalleHoras['faltas_injustificadas'],
                'horas_trabajadas' => $detalleHoras['horas_trabajadas'],
                'negro_sueldo_bruto' => $sueldosPactados[$empleadoId],
                'asignacion_familiar' => $empleado['asignacion_familiar'] ?? null,
                'spp_snp' => $contrato['plan_sp_codigo'],//PRO F
                'esta_jubilado' => $contrato['esta_jubilado'],
                'dscto_afp_seguro' => $descuento['porcentaje'], //porcentaje || porcentaje_65 || 0 
                'dscto_afp_seguro_explicacion' => $descuento['motivo'],
                //aqui guardar el total negro y total blanco para utilizarlo al momento de calcular los costos



            ];
            PlanillaMensualDetalleServicio::guardar($info, $dato['id']);
        }
    }
    /**
     * Helper para limpiar el loop principal de IFs de error
     */
    private function validarExistenciaIndices($id, $nombre, $horas, $contratos, $empleados): void
    {
        if (!isset($horas[$id]))
            throw new Exception("Faltan horas registradas para $nombre");
        if (!isset($contratos[$id]))
            throw new Exception("Falta contrato vigente para $nombre");
        if (!isset($empleados[$id]))
            throw new Exception("Faltan datos maestros para $nombre");
    }
}