<?php

namespace App\Services\Campania\Consumo;

use App\Models\AlmacenProductoSalida;
use App\Models\CampoCampania;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

/**
 * Reporte de consumo de productos de una campaña: salidas de almacén del campo en el rango de la campaña, leídas en
 * el momento (sin copias ni archivos guardados). Una hoja con todas las categorías; se filtra con la tabla de Excel.
 */
class CampaniaConsumoExcel
{
    private const ENCABEZADOS = ['N°', 'Kardex', 'Campaña', 'Fecha', 'Campo', 'Producto', 'Categoría', 'Cantidad', 'Costo unitario', 'Total costo'];

    public function generar(CampoCampania $campania): Spreadsheet
    {
        $salidas = AlmacenProductoSalida::with('producto.categoria')
            ->where('campo_nombre', $campania->campo)
            ->whereDate('fecha_reporte', '>=', $campania->fecha_inicio)
            ->when($campania->fecha_fin, fn($q) => $q->whereDate('fecha_reporte', '<=', $campania->fecha_fin))
            ->orderBy('fecha_reporte')
            ->orderBy('id')
            ->get();

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('CONSUMOS');
        $hoja->fromArray(self::ENCABEZADOS, null, 'A1');

        $fila = 2;
        foreach ($salidas as $i => $s) {
            $hoja->fromArray([
                $i + 1,
                mb_strtoupper($s->tipo_kardex ?? '-'),
                $campania->nombre_campania,
                ExcelDate::PHPToExcel($s->fecha_reporte),
                $s->campo_nombre,
                mb_strtoupper($s->producto?->nombre_completo ?? ''),
                mb_strtoupper($s->producto?->categoria?->descripcion ?? ''),
                (float) $s->cantidad,
                null,
                (float) $s->total_costo,
            ], null, "A{$fila}");
            $hoja->setCellValue("I{$fila}", "=IF(H{$fila}=0,0,J{$fila}/H{$fila})");
            $fila++;
        }

        $ultima = max($fila - 1, 2);
        $tabla = new Table("A1:J{$ultima}", 'ConsumosTable');
        $tabla->setStyle((new TableStyle())->setTheme(TableStyle::TABLE_STYLE_MEDIUM2)->setShowRowStripes(true));
        $hoja->addTable($tabla);

        $total = $ultima + 1;
        $hoja->setCellValue("A{$total}", 'TOTALES');
        $hoja->setCellValue("H{$total}", "=SUBTOTAL(109,H2:H{$ultima})");
        $hoja->setCellValue("J{$total}", "=SUBTOTAL(109,J2:J{$ultima})");
        $hoja->getStyle("A{$total}:J{$total}")->getFont()->setBold(true);

        $hoja->getStyle("D2:D{$ultima}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $hoja->getStyle("H2:J{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'J') as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }
        $hoja->freezePane('A2');

        return $libro;
    }

    public function nombreArchivo(CampoCampania $campania): string
    {
        return 'CONSUMO_' . mb_strtoupper(Str::slug("{$campania->campo}_{$campania->nombre_campania}", '_')) . '.xlsx';
    }
}
