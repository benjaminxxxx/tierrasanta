<?php

namespace App\Services\Campania\Exports;

use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Str;


class ExportCampaniaServicio
{
    /*
    public function generarExcelMensual(object $config, array $datos): string
    {
        // 1. Cargar la plantilla
        $spreadsheet = ExcelHelper::cargarPlantilla('bdd_campo.xlsx');
        $hoja = $spreadsheet->getSheetByName('FORMATO');

        if (!$hoja) {
            throw new Exception("No se ha configurado un formato para el documento a exportar.");
        }

        // 2. Configurar encabezados y títulos
        $nuevoNombre = mb_strtoupper(Str::slug($config->campo, '_'));
        $hoja->setTitle($nuevoNombre);
        $hoja->setCellValue("D1", "RESUMEN CAMPO: {$nuevoNombre}");

        // 3. Llenado de datos
        $fila = 6;
        foreach ($datos as $dato) {
            $hoja->setCellValue("A{$fila}", $dato['fecha'] ?? '');
            $hoja->setCellValue("B{$fila}", $dato['tipo_cambio'] ?? '');
            $hoja->setCellValue("C{$fila}", $dato['campania'] ?? '');
            $hoja->setCellValue("D{$fila}", $dato['horas'] ?? '');
            $hoja->setCellValue("E{$fila}", $dato['planilla_nombre'] ?? '');
            $hoja->setCellValue("F{$fila}", $dato['planilla_h'] ?? '');
            $hoja->setCellValue("G{$fila}", $dato['planilla_m'] ?? '');
            $hoja->setCellValue("H{$fila}", $dato['cuadrilla_fija_cantidad'] ?? '');
            $hoja->setCellValue("I{$fila}", $dato['cuadrilla_fija_costo'] ?? '');
            $hoja->setCellValue("J{$fila}", $dato['cuadrilla_cantidad'] ?? '');
            $hoja->setCellValue("K{$fila}", $dato['cuadrilla_costo'] ?? '');
            $hoja->setCellValue("L{$fila}", $dato['mano_obra'] ?? '');
            $hoja->setCellValue("M{$fila}", $dato['cantidad_jornales'] ?? '');
            $hoja->setCellValue("N{$fila}", $dato['costo'] ?? '');
            $hoja->setCellValue("O{$fila}", $dato['maquinaria'] ?? '');
            $hoja->setCellValue("P{$fila}", $dato['maquinaria_costo'] ?? '');
            $hoja->setCellValue("Q{$fila}", $dato['consumo_fertilizante_cantidad'] ?? '');
            $hoja->setCellValue("R{$fila}", $dato['consumo_fertilizante_nombre_comercial'] ?? '');
            $hoja->setCellValue("S{$fila}", $dato['consumo_fertilizante_orden_compra'] ?? '');
            $hoja->setCellValue("T{$fila}", $dato['consumo_fertilizante_tienda_comercial'] ?? '');
            $hoja->setCellValue("U{$fila}", $dato['consumo_fertilizante_factura'] ?? '');
            $hoja->setCellValue("V{$fila}", $dato['consumo_fertilizante_costo'] ?? '');
            $hoja->setCellValue("W{$fila}", $dato['consumo_pesticida_cantidad'] ?? '');
            $hoja->setCellValue("X{$fila}", $dato['consumo_pesticida_nombre_comercial'] ?? '');
            $hoja->setCellValue("Y{$fila}", $dato['consumo_pesticida_orden_compra'] ?? '');
            $hoja->setCellValue("Z{$fila}", $dato['consumo_pesticida_tienda_comercial'] ?? '');
            $hoja->setCellValue("AA{$fila}", $dato['consumo_pesticida_factura'] ?? '');
            $hoja->setCellValue("AB{$fila}", $dato['consumo_pesticida_costo'] ?? '');
            $hoja->setCellValue("AC{$fila}", $dato['costo_fijo'] ?? '');
            $hoja->setCellValue("AD{$fila}", $dato['costo_fijo_costo'] ?? '');
            $hoja->setCellValue("AE{$fila}", $dato['costo_operativo'] ?? '');
            $hoja->setCellValue("AF{$fila}", $dato['costo_operativo_costo'] ?? '');

            $fila++;
        }

        // 4. Definir rutas y nombres
        $folderPath = 'reporte/' . date('Y-m');
        $fileName = 'BDD_CAMPAÑA_' . mb_strtoupper(Str::slug($config->nombre_campania)) . 
                    '_CAMPO_' . mb_strtoupper(Str::slug($config->campo)) . '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        // 5. Almacenamiento
        Storage::disk('public')->makeDirectory($folderPath);
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }*/
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
