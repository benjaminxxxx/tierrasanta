<?php

namespace App\Services\Campania\Exports;

use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Str;


class ExportCampaniaServicio
{
 
    public function generarExcelMensual(object $config, array $datos): string
    {
        $spreadsheet = ExcelHelper::cargarPlantilla('bdd_campo.xlsx');
        $hoja = $spreadsheet->getSheetByName('FORMATO');

        if (!$hoja) {
            throw new Exception("No se ha configurado un formato para el documento a exportar.");
        }

        $nuevoNombre = mb_strtoupper(Str::slug($config->campo, '_'));
        $hoja->setTitle($nuevoNombre);
        $hoja->setCellValue("B1", "RESUMEN CAMPO: {$nuevoNombre}");
        $hoja->setCellValue("A2", $config->area ?? '');

        // Datos comienzan en la fila 5 (encabezados ocupan las filas 3-4 del nuevo diseño)
        $fila = 5;
        foreach ($datos as $dato) {
            $hoja->setCellValue("A{$fila}", $dato['fecha'] ?? '');
            $hoja->setCellValue("B{$fila}", $dato['campania'] ?? '');
            $hoja->setCellValue("C{$fila}", $dato['campo'] ?? '');
            $hoja->setCellValue("D{$fila}", $dato['tipo_gasto'] ?? '');
            $hoja->setCellValue("E{$fila}", $dato['detalle_labor'] ?? '');
            $hoja->setCellValue("F{$fila}", $dato['trabajador'] ?? '');
            $hoja->setCellValue("G{$fila}", $dato['horas'] ?? '');
            $hoja->setCellValue("H{$fila}", $dato['cantidad_jornales'] ?? '');
            $hoja->setCellValue("I{$fila}", $dato['cantidad'] ?? '');
            $hoja->setCellValue("J{$fila}", $dato['proveedor'] ?? '');
            $hoja->setCellValue("K{$fila}", $dato['n_documento'] ?? '');
            $hoja->setCellValue("L{$fila}", $dato['costo'] ?? '');
            $hoja->setCellValue("M{$fila}", $dato['observacion'] ?? '');

            $fila++;
        }

        $folderPath = 'reporte/' . date('Y-m');
        $fileName = 'BDD_CAMPAÑA_' . mb_strtoupper(Str::slug($config->nombre_campania)) .
            '_CAMPO_' . mb_strtoupper(Str::slug($config->campo)) . '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        Storage::disk('public')->makeDirectory($folderPath);
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
}
