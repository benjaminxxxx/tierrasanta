<?php

namespace App\Exports\Cuadrilla;

use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InformeGeneralCuadrilla
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function __invoke()
    {
        try {
            // 1️⃣ Cargar plantilla
            $spreadsheet = ExcelHelper::cargarPlantilla('reporte_cuadrillero_informe_general.xlsx');

            $hoja = $spreadsheet->getSheetByName('INFORME GENERAL CUADRILLA');

            if (!$hoja) {
                throw new Exception("La plantilla no contiene la hoja 'INFORME GENERAL CUADRILLA'.");
            }

            // 2️⃣ Leer tabla INFORME_GENERAL
            $table = $hoja->getTableByName('INFORME_GENERAL');

            if (!$table) {
                throw new Exception("La plantilla no tiene una tabla llamada INFORME_GENERAL.");
            }

            // 3️⃣ Primera fila debajo de los headers
            $fila = ExcelHelper::primeraFila($table) + 1;

            $contador = 1;

            foreach ($this->data['registros'] as $reg) {

                // FECHA EXCEL (si viene string dd/mm/yyyy)
                $fechaExcel = ExcelDate::PHPToExcel(Carbon::parse($reg['fecha']));

                $hoja->setCellValue("A{$fila}", $contador);
                $hoja->setCellValue("B{$fila}", $fechaExcel);
                $hoja->setCellValue("C{$fila}", $reg['codigo_grupo']);
                $hoja->setCellValue("D{$fila}", $reg['nombres']);
                $hoja->setCellValue("E{$fila}", $reg['costo_personalizado_dia']);
                $hoja->setCellValue("F{$fila}", $reg['total_horas']); // Horas Registradas
                $hoja->setCellValue("G{$fila}", $reg['horas_detalladas']);
                $hoja->setCellValue("H{$fila}", $reg['costo_dia']);
                $hoja->setCellValue("I{$fila}", $reg['total_bono']);
                $hoja->setCellValue("J{$fila}", $reg['costo_total'] ?? ($reg['costo_dia'] + $reg['total_bono']));
                $hoja->setCellValue("K{$fila}", $reg['esta_pagado'] ? 'Sí' : 'No');
                $hoja->setCellValue("L{$fila}", $reg['bono_esta_pagado'] ? 'Sí' : 'No');
                $hoja->setCellValue("M{$fila}", $reg['detalle_campos']); // concatenado por comas

                // FORMATO FECHA
                $hoja->getStyle("B{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode('DD/MM/YYYY');

                $contador++;
                $fila++;
            }

            // 4️⃣ Actualizar tamaño de tabla
            ExcelHelper::actualizarRangoTabla($table, $fila - 1);

            // 5️⃣ Descargar archivo temporal
            $writer = new Xlsx($spreadsheet);
            $filename = 'informe_general_cuadrilla.xlsx';

            return ExcelHelper::($spreadsheet, $filename);

        } catch (\Throwable $th) {
            throw new Exception("Error al generar informe: " . $th->getMessage());
        }
    }
}