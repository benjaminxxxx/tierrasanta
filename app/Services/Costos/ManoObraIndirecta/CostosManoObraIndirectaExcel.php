<?php

namespace App\Services\Costos\ManoObraIndirecta;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel de la mano de obra indirecta: hoja "Resumen" (secciones → total, con fórmulas que apuntan a cada hoja),
 * una hoja por sección con su detalle agrupado (subtotal por grupo con SUBTOTAL, total de la sección) y "Avisos".
 * Todo con fórmulas para que se pueda seguir la suma desde el detalle hasta el total.
 */
class CostosManoObraIndirectaExcel
{
    private const MONEDA = '#,##0.00';
    private const COLUMNAS = ['Fecha', 'Grupo', 'Detalle', 'Horas', 'Cantidad', 'Costo', '% blanco', 'Blanco', 'Negro', 'Nota'];

    /** Genera y guarda el Excel; devuelve la ruta relativa al disco public. */
    public function guardar(array $calculo, ?string $usuario = null): string
    {
        $libro = $this->generar($calculo, $usuario);
        $ruta = sprintf('costos/mano_obra_indirecta/%d/MANO_OBRA_INDIRECTA_%d_%02d.xlsx', $calculo['anio'], $calculo['anio'], $calculo['mes']);
        Storage::disk('public')->makeDirectory(dirname($ruta));
        (new Xlsx($libro))->save(Storage::disk('public')->path($ruta));
        return $ruta;
    }

    public function generar(array $calculo, ?string $usuario = null): Spreadsheet
    {
        $libro = new Spreadsheet();
        $resumen = $libro->getActiveSheet()->setTitle('Resumen');
        $periodo = ucfirst(Carbon::create($calculo['anio'], $calculo['mes'], 1)->translatedFormat('F Y'));

        $resumen->setCellValue('A1', "Mano de obra indirecta (FDM) – {$periodo}");
        $resumen->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $resumen->setCellValue('A2', 'Calculado el ' . now()->format('d/m/Y H:i') . ($usuario ? " por {$usuario}" : '')
            . ($calculo['concluyente'] ? '' : '  ·  NO CONCLUYENTE: ver hoja Avisos'));
        if (!$calculo['concluyente']) {
            $resumen->getStyle('A2')->getFont()->getColor()->setRGB('B91C1C');
        }
        $this->encabezado($resumen, 4, ['Sección', 'Blanco', 'Negro', 'Total', 'Explicación']);

        $fila = 5;
        $primera = $fila;
        foreach ($calculo['secciones'] as $seccion) {
            $hoja = $this->hojaSeccion($libro, $seccion);
            $ref = "'" . $hoja['titulo'] . "'!";
            $resumen->setCellValue("A{$fila}", $seccion['titulo']);
            $resumen->setCellValue("B{$fila}", "={$ref}H{$hoja['fila_total']}");
            $resumen->setCellValue("C{$fila}", "={$ref}I{$hoja['fila_total']}");
            $resumen->setCellValue("D{$fila}", "=B{$fila}+C{$fila}");
            $resumen->setCellValue("E{$fila}", $seccion['explicacion']);
            $resumen->getCell("A{$fila}")->getHyperlink()->setUrl("sheet://{$ref}A1");
            $fila++;
        }
        $resumen->setCellValue("A{$fila}", 'TOTAL MANO DE OBRA INDIRECTA');
        foreach (['B', 'C', 'D'] as $col) {
            $resumen->setCellValue("{$col}{$fila}", "=SUM({$col}{$primera}:{$col}" . ($fila - 1) . ')');
        }
        $resumen->getStyle("A{$fila}:D{$fila}")->getFont()->setBold(true);
        $resumen->getStyle("A{$fila}:D{$fila}")->getBorders()->getTop()->setBorderStyle('thin');
        $resumen->getStyle("B{$primera}:D{$fila}")->getNumberFormat()->setFormatCode(self::MONEDA);
        $resumen->getColumnDimension('A')->setWidth(42);
        foreach (['B', 'C', 'D'] as $col) {
            $resumen->getColumnDimension($col)->setWidth(15);
        }
        $resumen->getColumnDimension('E')->setWidth(110);
        $resumen->getStyle("E{$primera}:E{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        $this->hojaAvisos($libro, $calculo['avisos']);
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    /** @return array{titulo:string, fila_total:int} */
    private function hojaSeccion(Spreadsheet $libro, array $seccion): array
    {
        $titulo = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $seccion['titulo']), 0, 31);
        $hoja = $libro->createSheet()->setTitle($titulo);
        $hoja->setCellValue('A1', $seccion['titulo']);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $hoja->setCellValue('A2', $seccion['explicacion']);
        $hoja->mergeCells('A2:J2');
        $hoja->getStyle('A2')->getAlignment()->setWrapText(true);
        $hoja->getRowDimension(2)->setRowHeight(45);
        $this->encabezado($hoja, 4, self::COLUMNAS);

        $fila = 5;
        $inicioSeccion = $fila;
        foreach ($seccion['grupos'] as $grupo) {
            $desde = $fila;
            foreach ($grupo['filas'] as $f) {
                $hoja->setCellValue("A{$fila}", $f['fecha'] ? Carbon::parse($f['fecha'])->format('d/m/Y') : '');
                $hoja->setCellValue("B{$fila}", $grupo['nombre']);
                $hoja->setCellValue("C{$fila}", $f['detalle']);
                $hoja->setCellValue("D{$fila}", $f['horas']);
                $hoja->setCellValue("E{$fila}", $f['cantidad']);
                $hoja->setCellValue("F{$fila}", $f['costo']);
                $hoja->setCellValue("G{$fila}", $f['pct_blanco'] / 100);
                // Blanco = costo × % blanco; negro = el resto (así se sigue la separación en la misma fila)
                $hoja->setCellValue("H{$fila}", "=ROUND(F{$fila}*G{$fila},2)");
                $hoja->setCellValue("I{$fila}", "=F{$fila}-H{$fila}");
                $hoja->setCellValue("J{$fila}", $f['nota']);
                $fila++;
            }
            $hasta = $fila - 1;
            $hoja->setCellValue("B{$fila}", "Subtotal {$grupo['nombre']}");
            foreach (['D', 'F', 'H', 'I'] as $col) {
                $hoja->setCellValue("{$col}{$fila}", "=SUBTOTAL(9,{$col}{$desde}:{$col}{$hasta})");
            }
            $hoja->getStyle("A{$fila}:J{$fila}")->getFont()->setBold(true);
            $hoja->getStyle("A{$fila}:J{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
            $fila++;
        }
        $filaTotal = $fila;
        $hoja->setCellValue("A{$fila}", 'TOTAL ' . mb_strtoupper($seccion['titulo']));
        foreach (['D', 'F', 'H', 'I'] as $col) {
            // SUBTOTAL ignora los subtotales de grupo: suma solo el detalle
            $hoja->setCellValue("{$col}{$fila}", $fila > $inicioSeccion ? "=SUBTOTAL(9,{$col}{$inicioSeccion}:{$col}" . ($fila - 1) . ')' : 0);
        }
        $hoja->getStyle("A{$fila}:J{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("A{$fila}:J{$fila}")->getBorders()->getTop()->setBorderStyle('medium');

        $hoja->getStyle("D5:F{$fila}")->getNumberFormat()->setFormatCode(self::MONEDA);
        $hoja->getStyle("G5:G{$fila}")->getNumberFormat()->setFormatCode('0.00%');
        $hoja->getStyle("H5:I{$fila}")->getNumberFormat()->setFormatCode(self::MONEDA);
        foreach (['A' => 11, 'B' => 34, 'C' => 40, 'D' => 9, 'E' => 10, 'F' => 13, 'G' => 9, 'H' => 13, 'I' => 13, 'J' => 60] as $col => $ancho) {
            $hoja->getColumnDimension($col)->setWidth($ancho);
        }
        $hoja->freezePane('A5');
        $hoja->setAutoFilter('A4:J' . max(4, $fila - 1));
        return ['titulo' => $titulo, 'fila_total' => $filaTotal];
    }

    private function hojaAvisos(Spreadsheet $libro, array $avisos): void
    {
        $hoja = $libro->createSheet()->setTitle('Avisos');
        $this->encabezado($hoja, 1, ['Tipo', 'Aviso']);
        $fila = 2;
        foreach ($avisos as $a) {
            $hoja->setCellValue("A{$fila}", $a['no_concluyente'] ? 'No concluyente' : 'Revisar');
            $hoja->setCellValue("B{$fila}", $a['texto']);
            $fila++;
        }
        if (!$avisos) {
            $hoja->setCellValue('B2', 'Sin avisos: el cálculo es concluyente.');
        }
        $hoja->getColumnDimension('A')->setWidth(16);
        $hoja->getColumnDimension('B')->setWidth(130);
        $hoja->getStyle('B2:B' . max(2, $fila))->getAlignment()->setWrapText(true);
    }

    private function encabezado(Worksheet $hoja, int $fila, array $columnas): void
    {
        foreach ($columnas as $i => $texto) {
            $hoja->setCellValue([$i + 1, $fila], $texto);
        }
        $rango = "A{$fila}:" . chr(64 + count($columnas)) . $fila;
        $hoja->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    }
}
