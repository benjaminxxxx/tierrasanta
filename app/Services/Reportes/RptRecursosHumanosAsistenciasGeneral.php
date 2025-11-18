<?php

namespace App\Services\Reportes;

use App\Support\ExcelHelper;
use Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RptRecursosHumanosAsistenciasGeneral
{
    public function descargarInforme(array $registros, $fechaInicio, $fechaFin, $grupoSeleccionado, $filtroNombres)
    {
        $data = [
            'registros' => $registros,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'codigo_grupo' => $grupoSeleccionado,
            'nombre_trabajador' => $filtroNombres,
        ];

        // 1️⃣ Cargar plantilla
        $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_planilla_informe_general.xlsx');

        $hoja = $spreadsheet->getSheetByName('INFORME GENERAL PLANILLA');

        if (!$hoja) {
            throw new Exception("La plantilla no contiene la hoja 'INFORME GENERAL PLANILLA'.");
        }

        // 2️⃣ Ubicar tabla
        $table = $hoja->getTableByName('INFORME_GENERAL_PLANILLA');

        if (!$table) {
            throw new Exception("La plantilla no tiene una tabla llamada INFORME_GENERAL_PLANILLA.");
        }

        // 3️⃣ Cabecera
        $hoja->setCellValue("C2", $data['fecha_inicio']);
        $hoja->setCellValue("C3", $data['fecha_fin']);
        $hoja->setCellValue("C4", $data['codigo_grupo']);
        $hoja->setCellValue("C5", $data['nombre_trabajador']);

        // 4️⃣ Cargar registros
        $filaInicial = ExcelHelper::primeraFila($table);
        $fila = $filaInicial + 1;
        $contador = 1;

        foreach ($data['registros'] as $reg) {

            $fechaExcel = ExcelDate::PHPToExcel($reg['fecha']);

            $hoja->setCellValue("A{$fila}", $contador);
            $hoja->setCellValue("B{$fila}", $fechaExcel);
            $hoja->setCellValue("C{$fila}", $reg['codigo_grupo']);
            $hoja->setCellValue("D{$fila}", $reg['nombres']);
            $hoja->setCellValue("E{$fila}", $reg['asistencia']);
            $hoja->setCellValue("F{$fila}", $reg['costo_x_hora']);
            $hoja->setCellValue("G{$fila}", $reg['total_horas']);
            $hoja->setCellValue("H{$fila}", '=F' . $fila . '*G' . $fila);
            $hoja->setCellValue("I{$fila}", $reg['total_bono']);
            $hoja->setCellValue("J{$fila}", '=H' . $fila . '+I' . $fila);
            $hoja->setCellValue("K{$fila}", $reg['detalle_campos']);

            // Formato fecha
            $hoja->getStyle("B{$fila}")
                ->getNumberFormat()
                ->setFormatCode('DD/MM/YYYY');

            $contador++;
            $fila++;
        }

        // 5️⃣ expandir tabla
        ExcelHelper::actualizarRangoTabla($table, $fila - 1);

        // 6️⃣ Totales
        $filaTotales = $fila;

        $hoja->setCellValue("D{$filaTotales}", "TOTALES:");
        $hoja->getStyle("D{$filaTotales}")->getFont()->setBold(true);

        $hoja->setCellValue("G{$filaTotales}", "=SUM(G" . ($filaInicial + 1) . ":G" . ($fila - 1) . ")");
        $hoja->setCellValue("H{$filaTotales}", "=SUM(H" . ($filaInicial + 1) . ":H" . ($fila - 1) . ")");
        $hoja->setCellValue("I{$filaTotales}", "=SUM(I" . ($filaInicial + 1) . ":I" . ($fila - 1) . ")");
        $hoja->setCellValue("J{$filaTotales}", "=SUM(J" . ($filaInicial + 1) . ":J" . ($fila - 1) . ")");

        $hoja->getStyle("A{$filaTotales}:K{$filaTotales}")
            ->getFont()->setBold(true);

        return ExcelHelper::descargar($spreadsheet, 'informe_general_planilla.xlsx');
    }
}