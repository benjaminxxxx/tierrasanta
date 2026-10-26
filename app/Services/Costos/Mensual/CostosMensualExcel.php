<?php

namespace App\Services\Costos\Mensual;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel de Costos → Mensual: campañas que reciben costo (con sus fechas), costos del mes y, si ya se aprobó,
 * la distribución por campaña.
 */
class CostosMensualExcel
{
    public function __construct(private CostosMensualConsulta $consulta)
    {
    }

    public function generar(int $anio, int $mes): Spreadsheet
    {
        $libro = new Spreadsheet();
        $this->hojaCampanias($libro->getActiveSheet(), $anio, $mes);
        $this->hojaCostos($libro->createSheet(), $anio, $mes);
        $this->hojaDistribucion($libro->createSheet(), $anio, $mes);
        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    public function nombreArchivo(int $anio, int $mes): string
    {
        return sprintf('COSTO_MENSUAL_%04d_%02d.xlsx', $anio, $mes);
    }

    private function hojaCampanias(Worksheet $h, int $anio, int $mes): void
    {
        $h->setTitle('CAMPAÑAS');
        $filas = collect($this->consulta->campanias($anio, $mes)['filas'])->whereNotNull('campania_id')->values();

        $this->encabezado($h, ['Campo', 'Área (ha)', 'Campaña', 'Inicio', 'Fin', 'Estado', 'Días en el mes', 'Días del mes', 'Avisos']);
        $fila = 2;
        foreach ($filas as $f) {
            $h->fromArray([
                $f['campo'],
                $f['area'],
                $f['campania'],
                ExcelDate::PHPToExcel(Carbon::parse($f['fecha_inicio'])),
                $f['fecha_fin'] ? ExcelDate::PHPToExcel(Carbon::parse($f['fecha_fin'])) : null,
                $f['fecha_fin'] ? 'Cerrada' : ($f['por_cerrar'] ? 'Abierta · por cerrar' : 'Abierta'),
                $f['dias'],
                $f['dias_mes'],
                collect($f['avisos'])->pluck('mensaje')->implode(' | '),
            ], null, "A{$fila}", true);
            $fila++;
        }
        $ultima = $this->tabla($h, 'CampaniasTable', 'I', $fila);
        $h->getStyle("D2:E{$ultima}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $h->getStyle("B2:B{$ultima}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $this->ajustar($h, 'I');
    }

    private function hojaCostos(Worksheet $h, int $anio, int $mes): void
    {
        $h->setTitle('COSTOS DEL MES');
        $costos = $this->consulta->costos($anio, $mes);

        $this->encabezado($h, ['Tipo', 'Concepto', 'Blanco', 'Negro', 'Total']);
        $fila = 2;
        foreach ($costos['filas'] as $f) {
            $h->fromArray([$f['tipo'], $f['nombre'], $f['blanco'], $f['negro']], null, "A{$fila}", true);
            $h->setCellValue("E{$fila}", "=C{$fila}+D{$fila}");
            $fila++;
        }
        $ultima = $this->tabla($h, 'CostosTable', 'E', $fila);
        $total = $ultima + 1;
        $h->setCellValue("A{$total}", 'TOTAL');
        foreach (['C', 'D', 'E'] as $col) {
            $h->setCellValue("{$col}{$total}", "=SUM({$col}2:{$col}{$ultima})");
        }
        $h->getStyle("A{$total}:E{$total}")->getFont()->setBold(true);
        $h->getStyle("C2:E{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
        $this->ajustar($h, 'E');
    }

    private function hojaDistribucion(Worksheet $h, int $anio, int $mes): void
    {
        $h->setTitle('DISTRIBUCIÓN');
        $filas = $this->consulta->distribucion($anio, $mes);
        if (!$filas) {
            $h->setCellValue('A1', 'Aún no se ha aprobado la distribución de este mes (botón "Distribuir costos").');
            $h->getColumnDimension('A')->setAutoSize(true);
            return;
        }

        $conceptos = CostosMensualReglas::CONCEPTOS;
        $ultimaCol = $this->col(7 + count($conceptos) + 1);
        $this->encabezado($h, array_merge(
            ['Campo', 'Campaña', 'Inicio', 'Fin', 'Días activos', 'Días del mes', '%'],
            array_column($conceptos, 1),
            ['Total']
        ));

        $fila = 2;
        foreach ($filas as $f) {
            $h->fromArray(array_merge([
                $f['campo'],
                $f['campania'],
                $f['fecha_inicio'] ? ExcelDate::PHPToExcel(Carbon::parse($f['fecha_inicio'])) : null,
                $f['fecha_fin'] ? ExcelDate::PHPToExcel(Carbon::parse($f['fecha_fin'])) : null,
                $f['dias_activos'],
                $f['dias_mes'],
                $f['porcentaje'],
            ], array_values($f['montos'])), null, "A{$fila}", true);
            $h->setCellValue("{$ultimaCol}{$fila}", '=SUM(H' . $fila . ':' . $this->col(7 + count($conceptos)) . $fila . ')');
            $fila++;
        }
        $ultima = $this->tabla($h, 'DistribucionTable', $ultimaCol, $fila);
        $total = $ultima + 1;
        $h->setCellValue("A{$total}", 'TOTAL');
        for ($i = 7; $i <= 7 + count($conceptos) + 1; $i++) {
            $c = $this->col($i);
            $h->setCellValue("{$c}{$total}", "=SUM({$c}2:{$c}{$ultima})");
        }
        $h->getStyle("A{$total}:{$ultimaCol}{$total}")->getFont()->setBold(true);
        $h->getStyle("C2:D{$ultima}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $h->getStyle("G2:G{$total}")->getNumberFormat()->setFormatCode('0.00%');
        $h->getStyle("H2:{$ultimaCol}{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
        $this->ajustar($h, $ultimaCol);
    }

    private function encabezado(Worksheet $h, array $titulos): void
    {
        $h->fromArray($titulos, null, 'A1');
        $h->freezePane('A2');
    }

    /** Tabla de Excel desde A1 hasta la última fila escrita; devuelve esa fila. */
    private function tabla(Worksheet $h, string $nombre, string $ultimaCol, int $siguiente): int
    {
        $ultima = max($siguiente - 1, 2);
        $tabla = new Table("A1:{$ultimaCol}{$ultima}", $nombre);
        $tabla->setStyle((new TableStyle())->setTheme(TableStyle::TABLE_STYLE_MEDIUM2)->setShowRowStripes(true));
        $h->addTable($tabla);

        return $ultima;
    }

    private function ajustar(Worksheet $h, string $ultimaCol): void
    {
        foreach (range(1, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ultimaCol)) as $i) {
            $h->getColumnDimension($this->col($i))->setAutoSize(true);
        }
    }

    private function col(int $i): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    }
}
