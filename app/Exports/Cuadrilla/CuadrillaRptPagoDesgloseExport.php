<?php

namespace App\Exports\Cuadrilla;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CuadrillaRptPagoDesgloseExport implements FromArray, WithTitle, WithEvents, WithStyles
{
    protected $data;
    private $denominations = [200, 100, 50, 20, 10, 5, 2, 1, 0.50, 0.20, 0.10];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return "Desglose Moneda";
    }

    private function getUniqueWorkers(): array
    {
        $workerNames = [];
        if (isset($this->data['tramos'][0]['pagos'])) {
            foreach ($this->data['tramos'][0]['pagos'] as $pago) {
                $workerNames[] = $pago['nombre'];
            }
        }
        return $workerNames;
    }

    public function array(): array
    {
        $rows = [];
        $workers = $this->getUniqueWorkers();

        // Fila 1 y 2: Títulos
        $rows[] = ["Desglose de Billetes y Monedas para Pago"];
        $rows[] = [''];

        // Fila 3: Cabecera de la tabla principal
        $header = ['N°', 'TRABAJADOR', 'TOTAL A PAGAR', 'MONTO REDONDEADO'];
        foreach ($this->denominations as $denom) {
            $header[] = 'S/ ' . number_format($denom, 2);
        }
        $rows[] = $header;

        // Fila 4: Fila oculta con valores numéricos para las fórmulas
        $denominationValues = ['', '', '', ''];
        foreach ($this->denominations as $denom) {
            $denominationValues[] = $denom;
        }
        $rows[] = $denominationValues;

        $startRow = 5;
        $workerIndex = 0;

        foreach ($workers as $workerName) {
            $currentRow = $startRow + $workerIndex;
            $row = [];
            $row[] = $workerIndex + 1;
            $row[] = $workerName;

            $totalColIndex = 2 + count($this->data['tramos']) + 3;
            $totalColLetter = Coordinate::stringFromColumnIndex($totalColIndex);
            $consolidadoRow = 6 + $workerIndex;
            $row[] = "=Consolidado!{$totalColLetter}{$consolidadoRow}";
            $row[] = "=FLOOR(C{$currentRow}, 0.1)";

            $remainderFormula = "D{$currentRow}";
            $colIndex = 5;
            foreach ($this->denominations as $index => $denom) {
                $currentColLetter = Coordinate::stringFromColumnIndex($colIndex);
                $denominationCell = $currentColLetter . '4';
                if ($index === 0) {
                    $row[] = "=INT({$remainderFormula}/{$denominationCell})";
                } else {
                    $prevColLetter = Coordinate::stringFromColumnIndex($colIndex - 1);
                    $row[] = "=INT( ( {$remainderFormula} - SUMPRODUCT(\$E$4:{$prevColLetter}$4, E{$currentRow}:{$prevColLetter}{$currentRow}) ) / {$denominationCell} )";
                }
                $colIndex++;
            }
            $rows[] = $row;
            $workerIndex++;
        }

        // Fila de totales de la tabla principal
        $totalRow = ['', '', 'TOTALES', ''];
        $startDataRow = $startRow;
        $endDataRow = $startRow + count($workers) - 1;
        if ($endDataRow >= $startDataRow) {
            $totalAmountFormula = "=SUM(D{$startDataRow}:D{$endDataRow})";
            $totalRow[3] = $totalAmountFormula;
            for ($i = 5; $i <= (4 + count($this->denominations)); $i++) {
                $colLetter = Coordinate::stringFromColumnIndex($i);
                $totalRow[] = "=SUM({$colLetter}{$startDataRow}:{$colLetter}{$endDataRow})";
            }
        }
        $rows[] = $totalRow;

        // Dos líneas de respeto
        $rows[] = [''];
        $rows[] = [''];

        // Cabecera de la tabla resumen
        $rows[] = ['Resumen de Billetes y Monedas', '', '', ''];
        $rows[] = ['N°', 'Descripción', 'Cantidad', 'Monto Total'];

        // Contenido de la tabla resumen
        $totalsRowIndex = $endDataRow + 1;
        $colIndex = 5; // La columna 'E' es donde empiezan los totales de las denominaciones
        $summaryIndex = 1;

        foreach ($this->denominations as $denom) {
            $type = ($denom >= 10) ? 'Billetes' : 'Monedas';
            $description = sprintf('%s de S/ %.2f', $type, number_format($denom, 2));

            $quantityColLetter = Coordinate::stringFromColumnIndex($colIndex);
            $quantityFormula = "={$quantityColLetter}{$totalsRowIndex}";

            $denomValueColLetter = Coordinate::stringFromColumnIndex($colIndex);
            $summaryCurrentRow = count($rows) + 1;
            // Formula Monto = Cantidad * ValorDenominacion (de la fila oculta 4)
            $amountFormula = "=C{$summaryCurrentRow}*{$denomValueColLetter}4";

            $rows[] = [$summaryIndex, $description, $quantityFormula, $amountFormula];
            $colIndex++;
            $summaryIndex++;
        }

        // Fila de Total General del resumen
        $summaryStartDataRow = count($rows) - count($this->denominations) + 1;
        $summaryEndDataRow = count($rows);
        $totalGeneralFormula = "=SUM(D{$summaryStartDataRow}:D{$summaryEndDataRow})";
        $rows[] = ['', 'TOTAL GENERAL', '', $totalGeneralFormula];

        return $rows;
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $workers = $this->getUniqueWorkers();
                $lastColLetter = Coordinate::stringFromColumnIndex(4 + count($this->denominations));

                // === ESTILOS TABLA PRINCIPAL ===
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension('4')->setVisible(false);

                // Cabecera con WrapText
                $sheet->getStyle("A3:{$lastColLetter}3")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true, // <-- AQUÍ SE APLICA EL AJUSTE DE TEXTO
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension('3')->setRowHeight(30); // Altura ajustada

                $firstDataRow = 5;
                $lastDataRow = $firstDataRow + count($workers) - 1;
                $sheet->getStyle("A{$firstDataRow}:{$lastColLetter}{$lastDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $totalRowIndex = $lastDataRow + 1;
                $sheet->getStyle("A{$totalRowIndex}:{$lastColLetter}{$totalRowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("C{$totalRowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("C{$firstDataRow}:D{$totalRowIndex}")->getNumberFormat()->setFormatCode('"S/ " #,##0.00');
                $sheet->getStyle("E{$firstDataRow}:{$lastColLetter}{$totalRowIndex}")->getNumberFormat()->setFormatCode('#,##0');

                // Anchos de Columna de la tabla principal
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(18);
                for ($i = 5; $i <= (4 + count($this->denominations)); $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(10);
                }

                // --- ESTILOS PARA LA NUEVA TABLA RESUMEN ---
                $summaryTitleRow = $totalRowIndex + 3;
                $summaryHeaderRow = $summaryTitleRow + 1;
                $summaryFirstDataRow = $summaryHeaderRow + 1;
                $summaryLastDataRow = $summaryFirstDataRow + count($this->denominations) - 1;
                $summaryTotalRow = $summaryLastDataRow + 1;

                // Título del resumen
                $sheet->mergeCells("A{$summaryTitleRow}:D{$summaryTitleRow}");
                $sheet->getStyle("A{$summaryTitleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Cabecera de la tabla resumen
                $sheet->getStyle("A{$summaryHeaderRow}:D{$summaryHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // Cuerpo de la tabla resumen
                $sheet->getStyle("A{$summaryFirstDataRow}:D{$summaryLastDataRow}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$summaryFirstDataRow}:A{$summaryLastDataRow}")
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar N°
                $sheet->getStyle("C{$summaryFirstDataRow}:C{$summaryLastDataRow}")
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar Cantidad

                // Fila Total General del resumen
                $sheet->mergeCells("B{$summaryTotalRow}:C{$summaryTotalRow}");
                $sheet->getStyle("A{$summaryTotalRow}:D{$summaryTotalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("B{$summaryTotalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Formatos de número para la tabla resumen
                $sheet->getStyle("C{$summaryHeaderRow}:C{$summaryLastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("D{$summaryHeaderRow}:D{$summaryTotalRow}")->getNumberFormat()->setFormatCode('"S/ " #,##0.00');

                // Anchos de Columna específicos para la tabla resumen
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(18);

                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getPageSetup()->setHorizontalCentered(true);
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}