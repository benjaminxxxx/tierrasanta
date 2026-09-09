<?php

namespace App\Services\Campo\Costos;

use App\Models\CostoMensual;
use App\Models\ResumenCostoDiario;
use App\Services\Campania\Data\DataReporteCampoServicio;
use App\Services\Planilla\PlanillaServicio;
use App\Support\ExcelHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use Exception;

class ConsolidarReporteMensualCostos
{
    /**
     * Ejecuta el proceso de consolidación mensual de costos.
     *
     * @param int $anio
     * @param int $mes
     * @return CostoMensual
     */
    public function ejecutar(int $anio, int $mes): CostoMensual
    {
        // 1. Recopilar/calcular totales consolidados por campo
        $totalesCalculados = $this->recopilarTotalesPorCampo($anio, $mes);

        // 2. Generar archivo Excel multipestaña y obtener la ruta guardada
        $reporteFilePath = $this->generarReporteExcelMensual($anio, $mes);

        // 3. Crear o actualizar el registro en la tabla costos_mensuales
        $costoMensual = $this->guardarOActualizarCostoMensual(
            $anio,
            $mes,
            $totalesCalculados,
            $reporteFilePath
        );

        return $costoMensual;
    }

    /**
     * Recopila los totales calculados sumando la información real de todos los campos en el mes/año.
     *
     * @param int $anio
     * @param int $mes
     * @return array
     */
    public function recopilarTotalesPorCampo(int $anio, int $mes): array
    {
        // 1. Obtener la suma del costo real pagado de planilla desde el servicio
        $planillaMensual = collect(app(PlanillaServicio::class)->obtenerProyeccion($mes, $anio));
        $costoPlanillaPagado = (float) $planillaMensual->sum('pagado_sueldo_bruto_negro');

        // 2. Obtener la suma de los costos asignados a campo desde resumen_costo_diarios
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth()->format('Y-m-d');
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d');

        $costoPlanillaCalculado = (float) ResumenCostoDiario::where('origen_tipo', 'planilla')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->sum('costo_total');

        return [
            'costo_planilla' => round($costoPlanillaPagado, 2),
            'costo_planilla_calculado' => round($costoPlanillaCalculado, 2),
            'costo_cuadrilla_calculado' => 0.00,
            'costo_maquinaria_calculado' => 0.00,
            'costo_pesticida_calculado' => 0.00,
            'costo_fertilizante_calculado' => 0.00,
            'costo_gastos_generales_calculado' => 0.00,
        ];
    }

    /**
     * Genera el libro Excel consolidado mensual procesando la pestaña 'COSTO LABORAL PLANILLA'
     * y la pestaña 'COSTO POR CAMPO' con la data unificada de resumen_costo_diarios.
     *
     * @param int $anio
     * @param int $mes
     * @return string Ruta del archivo guardado en el storage público
     */
    public function generarReporteExcelMensual(int $anio, int $mes): string
    {
        // 1. Cargar la plantilla principal
        $spreadsheet = ExcelHelper::cargarPlantilla('bdd_costo_mensual.xlsx');

        // ==========================================
        // PARTE A: PESTAÑA 'COSTO LABORAL PLANILLA'
        // ==========================================
        $hojaPlanilla = $spreadsheet->getSheetByName('COSTO LABORAL PLANILLA');

        if (!$hojaPlanilla) {
            throw new Exception("No se ha configurado la pestaña 'COSTO LABORAL PLANILLA' en la plantilla bdd_costo_mensual.xlsx.");
        }

        // A1. Obtener datos de la planilla
        $planillaMensual = collect(app(PlanillaServicio::class)->obtenerProyeccion($mes, $anio));
        $totalRegistrosPlanilla = $planillaMensual->count();

        if ($totalRegistrosPlanilla === 0) {
            throw new Exception("No hay datos de planilla para el periodo seleccionado ({$mes}/{$anio}).");
        }

        // A2. Cabecera (Fila 5)
        $nombreMes = mb_strtoupper(Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName);
        $hojaPlanilla->setCellValue("A5", "{$nombreMes} {$anio}");
        $hojaPlanilla->setCellValue("C5", $planillaMensual->sum('pagado_sueldo_bruto_negro'));
        $hojaPlanilla->getStyle("C5")->getNumberFormat()->setFormatCode('#,##0.00');
        $hojaPlanilla->setCellValue("E5", $totalRegistrosPlanilla);
        $hojaPlanilla->setCellValue("F5", now()->format('d/m/Y H:i:s'));

        // A3. Poblar Tabla de Planilla
        $tablaPlanilla = $hojaPlanilla->getTableByName('tblCostoPlanillaMensual');
        $filaInicioPlanilla = $tablaPlanilla ? (ExcelHelper::primeraFila($tablaPlanilla) + 1) : 8;

        if ($totalRegistrosPlanilla > 1) {
            $hojaPlanilla->insertNewRowBefore($filaInicioPlanilla + 1, $totalRegistrosPlanilla - 1);
        }

        $filaActual = $filaInicioPlanilla;
        foreach ($planillaMensual as $index => $empleado) {
            $nombre = is_array($empleado) ? ($empleado['nombres'] ?? '') : ($empleado->nombres ?? '');
            $sueldoPagado = is_array($empleado) ? ($empleado['sueldo_pagado'] ?? 0) : ($empleado->sueldo_pagado ?? 0);
            $aportesTrabajador = is_array($empleado) ? ($empleado['aportes_trabajador'] ?? 0) : ($empleado->aportes_trabajador ?? 0);
            $aportesEmpleador = is_array($empleado) ? ($empleado['aportes_empleador'] ?? 0) : ($empleado->aportes_empleador ?? 0);
            $costoTotal = is_array($empleado)
                ? ($empleado['costo_total_empresa'] ?? $empleado['pagado_sueldo_bruto_negro'] ?? 0)
                : ($empleado->costo_total_empresa ?? $empleado->pagado_sueldo_bruto_negro ?? 0);

            $hojaPlanilla->setCellValue("A{$filaActual}", $index + 1);
            $hojaPlanilla->setCellValue("B{$filaActual}", $nombre);
            $hojaPlanilla->setCellValue("C{$filaActual}", $sueldoPagado);
            $hojaPlanilla->setCellValue("D{$filaActual}", $aportesTrabajador);
            $hojaPlanilla->setCellValue("E{$filaActual}", $aportesEmpleador);
            $hojaPlanilla->setCellValue("F{$filaActual}", $costoTotal);

            $hojaPlanilla->getStyle("C{$filaActual}:F{$filaActual}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            $filaActual++;
        }

        // A4. Fila de Totales al final de la tabla
        $filaTotalesPlanilla = $filaActual;
        $filaUltimoDatoPlanilla = $filaActual - 1;

        $hojaPlanilla->setCellValue("B{$filaTotalesPlanilla}", "TOTAL GENERAL");
        $hojaPlanilla->setCellValue("C{$filaTotalesPlanilla}", "=SUM(C{$filaInicioPlanilla}:C{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("D{$filaTotalesPlanilla}", "=SUM(D{$filaInicioPlanilla}:D{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("E{$filaTotalesPlanilla}", "=SUM(E{$filaInicioPlanilla}:E{$filaUltimoDatoPlanilla})");
        $hojaPlanilla->setCellValue("F{$filaTotalesPlanilla}", "=SUM(F{$filaInicioPlanilla}:F{$filaUltimoDatoPlanilla})");

        $hojaPlanilla->getStyle("B{$filaTotalesPlanilla}:F{$filaTotalesPlanilla}")->getFont()->setBold(true);
        $hojaPlanilla->getStyle("C{$filaTotalesPlanilla}:F{$filaTotalesPlanilla}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        if ($tablaPlanilla) {
            ExcelHelper::actualizarRangoTabla($tablaPlanilla, $filaTotalesPlanilla);
        }

        // ==========================================
        // PARTE B: PESTAÑA 'COSTO POR CAMPO'
        // ==========================================
        $hojaCampo = $spreadsheet->getSheetByName('COSTO POR CAMPO');

        if ($hojaCampo) {
            // B1. Obtener el rango de fechas para el mes completo
            $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d');

            // B2. Obtener data unificada mediante DataReporteCampoServicio
            $datosCampo = app(DataReporteCampoServicio::class)->obtenerDataUnificada($fechaInicio, $fechaFin);
            $totalDatosCampo = count($datosCampo);

            if ($totalDatosCampo > 0) {
                $filaInicioCampo = 5;

                // B4. Poblar datos en la hoja
                $filaCampo = $filaInicioCampo;
                foreach ($datosCampo as $dato) {
                    $hojaCampo->setCellValue("A{$filaCampo}", $dato['fecha'] ?? '');
                    $hojaCampo->setCellValue("B{$filaCampo}", $dato['campania'] ?? '');
                    $hojaCampo->setCellValue("C{$filaCampo}", $dato['campo'] ?? '');
                    $hojaCampo->setCellValue("D{$filaCampo}", $dato['tipo_gasto'] ?? '');
                    $hojaCampo->setCellValue("E{$filaCampo}", $dato['detalle_labor'] ?? '');
                    $hojaCampo->setCellValue("F{$filaCampo}", $dato['trabajador'] ?? '');
                    $hojaCampo->setCellValue("G{$filaCampo}", $dato['horas'] ?? '');
                    $hojaCampo->setCellValue("H{$filaCampo}", $dato['cantidad_jornales'] ?? '');
                    $hojaCampo->setCellValue("I{$filaCampo}", $dato['cantidad'] ?? '');
                    $hojaCampo->setCellValue("J{$filaCampo}", $dato['proveedor'] ?? '');
                    $hojaCampo->setCellValue("K{$filaCampo}", $dato['n_documento'] ?? '');
                    $hojaCampo->setCellValue("L{$filaCampo}", $dato['costo'] ?? '');
                    $hojaCampo->setCellValue("M{$filaCampo}", $dato['observacion'] ?? '');

                    // Formato numérico para el costo
                    $hojaCampo->getStyle("L{$filaCampo}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    $filaCampo++;
                }
            }
        }

        // L. Fila de Totales al final de la tabla
        $filaTotalesCampo = $filaCampo;
        $filaUltimoDatoCampo = $filaCampo - 1;

        $hojaCampo->setCellValue("K{$filaTotalesCampo}", "TOTAL GENERAL");
        $hojaCampo->setCellValue("L{$filaTotalesCampo}", "=SUM(L{$filaInicioCampo}:L{$filaUltimoDatoCampo})");

        $hojaCampo->getStyle("K{$filaTotalesCampo}:L{$filaTotalesCampo}")->getFont()->setBold(true);
        $hojaCampo->getStyle("K{$filaTotalesCampo}:L{$filaTotalesCampo}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // ==========================================
// PARTE C: PESTAÑA 'COSTO TOTAL'
// ==========================================
        $hojaCostoTotal = $spreadsheet->getSheetByName('COSTO TOTAL');

        if ($hojaCostoTotal) {
            // C1. Obtener la entidad de costos mensual de la BDD o del servicio
            $costoMensual = CostoMensual::where('anio', $anio)
                ->where('mes', $mes)
                ->first(); // O traerlo mediante tu servicio correspondiente

            // C2. Mapear Costos Pagados (Fila 5)
            $hojaCostoTotal->setCellValue("B5", (float) ($costoMensual->costo_planilla ?? 0));
            $hojaCostoTotal->setCellValue("C5", (float) ($costoMensual->costo_cuadrilla ?? 0));
            $hojaCostoTotal->setCellValue("D5", (float) ($costoMensual->costo_maquinaria ?? 0));
            $hojaCostoTotal->setCellValue("E5", (float) ($costoMensual->costo_pesticida ?? 0));
            $hojaCostoTotal->setCellValue("F5", (float) ($costoMensual->costo_fertilizante ?? 0));
            $hojaCostoTotal->setCellValue("G5", (float) ($costoMensual->costo_gastos_generales ?? 0));

            // C3. Mapear Costos por Campo Totalizados / Calculados (Fila 6)
            $hojaCostoTotal->setCellValue("B6", (float) ($costoMensual->costo_planilla_calculado ?? 0));
            $hojaCostoTotal->setCellValue("C6", (float) ($costoMensual->costo_cuadrilla_calculado ?? 0));
            $hojaCostoTotal->setCellValue("D6", (float) ($costoMensual->costo_maquinaria_calculado ?? 0));
            $hojaCostoTotal->setCellValue("E6", (float) ($costoMensual->costo_pesticida_calculado ?? 0));
            $hojaCostoTotal->setCellValue("F6", (float) ($costoMensual->costo_fertilizante_calculado ?? 0));
            $hojaCostoTotal->setCellValue("G6", (float) ($costoMensual->costo_gastos_generales_calculado ?? 0));


            // C6. Formato numérico en soles a toda la matriz
            $hojaCostoTotal->getStyle("B5:H7")
                ->getNumberFormat()
                ->setFormatCode('"S/" #,##0.00');
        }

        // ==========================================
        // PARTE C: GUARDAR ARCHIVO
        // ==========================================
        $mesFormateado = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $folderPath = "reporte/{$anio}-{$mesFormateado}";
        $fileName = "REPORTE_CONSOLIDADO_MENSUAL_{$anio}_{$mesFormateado}.xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        Storage::disk('public')->makeDirectory($folderPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
    /**
     * Crea o actualiza el registro en la tabla costos_mensuales para el año y mes dados.
     *
     * @param int $anio
     * @param int $mes
     * @param array $totalesCalculados
     * @param string $reporteFilePath
     * @return CostoMensual
     */
    public function guardarOActualizarCostoMensual(
        int $anio,
        int $mes,
        array $totalesCalculados,
        string $reporteFilePath
    ): CostoMensual {
        return CostoMensual::updateOrCreate(
            [
                'anio' => $anio,
                'mes' => $mes,
            ],
            array_merge($totalesCalculados, [
                'reporte_file' => $reporteFilePath,
                'estado' => 'consolidado',
                'calculado_en' => now(),
                'calculado_por' => auth()->id(),
            ])
        );
    }
}