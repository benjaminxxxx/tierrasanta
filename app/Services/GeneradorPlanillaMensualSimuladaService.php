<?php

namespace App\Services;

use App\Models\PlanMensual;
use Illuminate\Support\Facades\DB;
use App\Services\Planilla\PlanillaServicio;
use Exception;

class GeneradorPlanillaMensualSimuladaService
{
    protected PlanillaServicio $planillaServicio;
    protected PlanillaMensualServicio $planillaMensualServicio;

    public function __construct(
        PlanillaServicio $planillaServicio,
        PlanillaMensualServicio $planillaMensualServicio
    ) {
        $this->planillaServicio = $planillaServicio;
        $this->planillaMensualServicio = $planillaMensualServicio;
    }

    /**
     * Genera la apertura, persistencia de configuración, snapshot de descuentos AFP
     * y la proyección mensual completa con su archivo Excel asociado.
     *
     * @param int $anio Ejemplo: 2026
     * @param int $mes  Ejemplo: 8
     * @return array Resumen del proceso de proyección
     */
    public function generarPlanillaMensual(int $anio = 2026, int $mes = 8): array
    {
        return DB::transaction(function () use ($anio, $mes) {
            // 1. Apertura / Actualización de datos base de PlanMensual
            $planMensual = $this->guardarApertura($anio, $mes);

            // 2. Configuración predeterminada de porcentaje y parámetros legales
            $configuracionPendiente = $this->obtenerConfiguracionFija();

            // Guardar configuración general en la planilla mensual
            PlanillaMensualServicio::guardarConfiguracionEnPlanMensual(
                $planMensual->id,
                $configuracionPendiente
            );

            // 3. Generar el Snapshot obligatorio de comisiones/primas AFP - SNP
            PlanillaMensualServicio::snapshotDescuentosSp($planMensual->id, $mes, $anio);

            // 4. Ejecutar la Proyección General de la Planilla
            $resultado = $this->planillaServicio->generarProyeccion($mes, $anio);
            if (!empty($resultado['errores']) || $resultado['procesados'] < $resultado['total']) {
                // Si hay un resumen formateado, lo usamos en el mensaje de error
                $detallesError = '';
                if (!empty($resultado['resumen'])) {
                    $mensajes = array_column($resultado['resumen'], 'mensaje');
                    $detallesError = implode(' | ', $mensajes);
                } else {
                    $detallesError = "Se procesaron {$resultado['procesados']} de {$resultado['total']} empleados.";
                }

                throw new Exception("Error al generar la proyección de la planilla: {$detallesError}");
            }
            // 5. Generar Excel y guardar la ruta en el modelo
            $excelPath = $this->planillaServicio->generarExcelPlanilla($mes, $anio);
            $planMensual->update(['excel' => $excelPath]);

            return [
                'plan_mensual_id' => $planMensual->id,
                'excel_path'      => $excelPath,
                'resultado'       => $resultado,
            ];
        });
    }

    /**
     * Crea o actualiza el registro en `plan_mensuales` con los valores requeridos.
     */
    protected function guardarApertura(int $anio, int $mes): PlanMensual
    {
        $diasLaborables = 26;
        $totalHoras = $diasLaborables * 8; // 208
        $remuneracionBasica = 1167.66666667;

        return PlanMensual::updateOrCreate(
            ['mes' => $mes, 'anio' => $anio],
            [
                'dias_laborables'         => $diasLaborables,
                'total_horas'             => $totalHoras,
                'remuneracion_basica'     => $remuneracionBasica,
                'rmv'                     => 1130.0000,
                'asignacion_familiar'     => 113.0000,
                'gratificaciones'         => 16.6600,
                'essalud_gratificaciones' => 6.0000,
                'beta30'                  => 30.0000,
                'essalud'                 => 6.0000,
                'vida_ley'                => 0.6300,
                'pension_sctr'            => 0.5700,
                'essalud_eps'             => 0.5500,
                'rem_basica_essalud'      => 1130,
                'cts'                     => 9.7200,
            ]
        );
    }

    /**
     * Devuelve el conjunto de parámetros legales y porcentajes requeridos.
     */
    protected function obtenerConfiguracionFija(): array
    {
        return [
            'asignacion_familiar'     => '113.0000',
            'beta30'                  => '30.0000',
            'cts'                     => '9.7200',
            'essalud'                 => '6.0000',
            'essalud_eps'             => '0.5500',
            'essalud_gratificaciones' => '6.0000',
            'gratificaciones'         => '16.6600',
            'pension_sctr'            => '0.5700',
            'rmv'                     => '1130.0000',
            'vida_ley'                => '0.6300',
            'rem_basica_essalud'      => 1130,
        ];
    }
}