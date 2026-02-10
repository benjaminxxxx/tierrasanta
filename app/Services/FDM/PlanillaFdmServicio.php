<?php

namespace App\Services\FDM;

use App\Models\PlanMensualDetalle;
use App\Services\RecursosHumanos\Planilla\PlanillaMensualDetalleServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Support\CalculoHelper;
use App\Support\ExcelHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class PlanillaFdmServicio
{
    public static function calcularGastosPlanillaMensual(int $mes, int $anio)
    {
        //Obtenemos registros
        $registros = PlanillaRegistroDiarioServicio::obtenerRegistrosMensualesPorCampo('fdm', $mes, $anio);
        $registrosLicenciados = PlanillaRegistroDiarioServicio::obtenerRegistrosMensualesConLicenciasConsiderados($mes, $anio);
        $planillaMensualYSusCostos = PlanillaMensualDetalleServicio::obtenerRegistrosMensualesPorCampo($mes, $anio);
        $registros = $registros->merge($registrosLicenciados)->toArray();

        $dataProcesada = [];

        foreach ($registros as $registro) {
            $planEmpleadoId = $registro['plan_empleado_id'];
            $detallePlanilla = $planillaMensualYSusCostos[$planEmpleadoId] ?? null;

            if (!$detallePlanilla) {
                continue;
            }
            dd($detallePlanilla);
            // --- CÁLCULO DE COSTOS USANDO EL HELPER ---
            // Sueldo pactado total = blanco + negro
            $netoRecibidoReal = (float) $detallePlanilla['sueldo_blanco_pagado'] + (float) $detallePlanilla['sueldo_negro_pagado'];

            $calculo = CalculoHelper::calcularCostoLaborMinimal(
                $registro['total_horas'],                 // Horas del tramo específico
                (float) $detallePlanilla['total_horas'],   // Horas totales que el trabajador hizo en el mes
                $netoRecibidoReal,                        // Pactado real
                (float) $detallePlanilla['sueldo_blanco_pagado'], // Costo Blanco Empresa
            );

            // Añadimos los resultados al registro para el Excel
            $registro['gasto_blanco'] = $calculo['blanco'];
            $registro['gasto_negro'] = $calculo['negro'];
            $registro['gasto_total_prorrateado'] = $calculo['total'];

            $dataProcesada[] = $registro;
        }

        /*
        dd($dataProcesada);
          15 => array:13 [▼
            "fecha" => "06/02/2026"
            "plan_empleado_id" => 2175
            "documento" => "24679335"
            "empleado_nombre" => "CCANCHI NEIRA, JUAN LORENZO"
            "labor" => "LCG"
            "campo" => "-"
            "hora_inicio" => null
            "hora_salida" => null
            "total_horas" => 8.0
            "gasto_bono" => 0.0
            "gasto_blanco" => 2100.0
            "gasto_negro" => 62.96
            "gasto_total_prorrateado" => 2162.96
        ]
        ] */
        // 3. GENERACIÓN DEL EXCEL (Actualizado con nuevas columnas)
        $gastosAdicionales = []; // Aquí cargarías tus otros gastos si existen
        $rutaArchivo = self::generarExcelReporteFdm($dataProcesada, $gastosAdicionales, $anio, $mes);
        dd($rutaArchivo);
        return [
            'file' => $rutaArchivo,
            'detalle' => $dataProcesada,
            'total_blanco' => collect($dataProcesada)->sum('gasto_blanco'),
            'total_negro' => collect($dataProcesada)->sum('gasto_negro'),
        ];
    }
    /**
     * Crea y guarda el archivo Excel basado en la plantilla de gastos FDM.
     *
     * @param array $actividades Datos de los trabajadores y prorrateos
     * @param array $gastosAdicionales Datos de gastos extras por grupo
     * @param int $anio
     * @param int $mes
     * @return string Ruta relativa del archivo guardado
     * @throws Exception
     */
    public static function generarExcelReporteFdm(array $actividades, array $gastosAdicionales, int $anio, int $mes): string
    {
        $hojasRequeridas = ['GASTO_CUADRILLA_FDM', 'GASTO_CUADRILLA_FDM_ADICIONAL'];
        $hojas = ExcelHelper::cargarHojasDesdePlantilla('reporte_gasto_cuadrilla_fdm.xlsx', $hojasRequeridas);

        ['GASTO_CUADRILLA_FDM' => $sheet, 'GASTO_CUADRILLA_FDM_ADICIONAL' => $sheetAdicional] = $hojas;

        // --- Procesamiento Hoja Principal (Actividades) ---
        $fila = $sheet->getHighestDataRow();
        $index = $fila - 1;



        foreach ($actividades as $reporte) {

            $horaInicio = Carbon::parse($reporte['hora_inicio'])->format('H:i');
            $horaSalida = Carbon::parse($reporte['hora_salida'])->format('H:i');

            $sheet->setCellValue("A{$fila}", $index);
            $sheet->setCellValue("B{$fila}", $anio);
            $sheet->setCellValue("C{$fila}", $mes);
            $sheet->setCellValue("D{$fila}", $reporte['fecha']);
            $sheet->setCellValue("E{$fila}", $reporte['documento']);
            $sheet->setCellValue("F{$fila}", $reporte['empleado_nombre']);
            $sheet->setCellValue("G{$fila}", $reporte['labor']);
            $sheet->setCellValue("H{$fila}", $reporte['campo']);
            $sheet->setCellValue("I{$fila}", $reporte['total_horas']);
            $sheet->setCellValue("J{$fila}", $horaInicio);
            $sheet->setCellValue("K{$fila}", $horaSalida);
            $sheet->setCellValue("L{$fila}", $reporte['total_horas']);
            $sheet->setCellValue("M{$fila}", $reporte['costo_dia']);
            $sheet->setCellValue("N{$fila}", $reporte['gasto']);
            $sheet->setCellValue("O{$fila}", $reporte['gasto_bono']);
            $sheet->setCellValue("P{$fila}", "=N{$fila}+O{$fila}");
            $fila++;
            $index++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTALES');
        $sheet->setCellValue("N{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto])");
        $sheet->setCellValue("O{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto bono])");
        $sheet->setCellValue("P{$fila}", "=SUM(GASTO_CUADRILLA_FDM[Gasto total])");
        $sheet->getTableByName('GASTO_CUADRILLA_FDM')->setRange("A1:P" . ($fila - 1));

        // --- Procesamiento Hoja Gastos Adicionales ---
        $filaAdicional = max(2, $sheetAdicional->getHighestDataRow() + 1);
        foreach ($gastosAdicionales as $gasto) {
            $sheetAdicional->setCellValue("A{$filaAdicional}", $gasto['orden']);
            $sheetAdicional->setCellValue("B{$filaAdicional}", $gasto['grupo']);
            $sheetAdicional->setCellValue("C{$filaAdicional}", $gasto['descripcion']);
            $sheetAdicional->setCellValue("D{$filaAdicional}", $gasto['monto']);
            $sheetAdicional->setCellValue("E{$filaAdicional}", $gasto['fecha_gasto']);
            $sheetAdicional->setCellValue("F{$filaAdicional}", $gasto['fecha_contable']);
            $filaAdicional++;
        }

        $sheetAdicional->setCellValue("A{$filaAdicional}", 'TOTALES');
        $sheetAdicional->setCellValue("D{$filaAdicional}", "=SUM(GASTO_CUADRILLA_FDM_ADICIONAL[Monto])");
        $sheetAdicional->getTableByName('GASTO_CUADRILLA_FDM_ADICIONAL')->setRange("A1:F" . ($filaAdicional - 1));

        // --- Guardado del Archivo ---
        $folderPath = "gastos_cuadrilla_fdm/{$anio}";
        $fileName = "REPORTE_GASTO_CUADRILLA_FDM_{$anio}_{$mes}.xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        Storage::disk('public')->makeDirectory($folderPath);

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $spreadsheet = $sheet->getParent();
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
}
