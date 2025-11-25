<?php

namespace App\Services\Reportes;

use App\Support\ExcelHelper;
use Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RptProduccionPlanificacionCampania
{
    public function descargarReporteGeneral($registros, $campo, $campania)
    {

        // 1️⃣ Cargar plantilla
        $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_reporte_general_campania.xlsx');

        $hoja = $spreadsheet->getSheetByName('RESUMEN_CAMPANIA');

        if (!$hoja) {
            throw new Exception("La plantilla no contiene la hoja 'RESUMEN_CAMPANIA'.");
        }

        // 3️⃣ Cabecera
        $hoja->setCellValue("C2", $campo);
        $hoja->setCellValue("C3", $campania);

        // 4️⃣ Cargar registros
        $fila = 7;
        $filaInicioDatos = $fila;

        foreach ($registros as $reg) {

            $fechaExcel = ExcelDate::PHPToExcel($reg['fecha']);

            $hoja->setCellValue("A{$fila}", $reg->nombre_campania);
            $hoja->setCellValue("B{$fila}", $reg->campo);
            $hoja->setCellValue("C{$fila}", $reg->area);
            $hoja->setCellValue("D{$fila}", $reg->fecha_siembra);
            $hoja->setCellValue("E{$fila}", $reg->fecha_inicio);
            $hoja->setCellValue("F{$fila}", $reg->fecha_fin);
            $hoja->setCellValue("G{$fila}", $reg->pp_dia_cero_fecha_evaluacion);
            $hoja->setCellValue("H{$fila}", $reg->pp_dia_cero_numero_pencas_madre);
            $hoja->setCellValue("I{$fila}", $reg->pp_resiembra_fecha_evaluacion);
            $hoja->setCellValue("J{$fila}", $reg->pp_resiembra_numero_pencas_madre);

            $fila++;
        }

        // Guardamos la última fila con datos
        $filaFinDatos = $fila - 1;

        // 5️⃣ Agregar bordes a todo el rango A7:J(última fila)
        $rango = "A{$filaInicioDatos}:J{$filaFinDatos}";

        $hoja->getStyle($rango)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        return ExcelHelper::descargar($spreadsheet, 'resumen_campañas.xlsx');
    }
}