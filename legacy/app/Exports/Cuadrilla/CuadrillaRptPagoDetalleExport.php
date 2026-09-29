<?php

namespace App\Exports\Cuadrilla;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CuadrillaRptPagoDetalleExport implements FromArray, WithEvents, WithStyles, WithTitle
{
    protected $data;
    protected $periodo;

    public function __construct($data)
    {
        $this->data = $data;

        $this->periodo = CarbonPeriod::create(
            Carbon::parse($data['fecha_inicio']),
            Carbon::parse($data['fecha_fin'])
        )->toArray();
    }

    public function title(): string
    {
        return "Pagos";
    }

    public function array(): array
    {
        $rows = [];
        $filaInicial = 1;


        foreach ($this->data['tramos'] as $index => $tramo) {

            $totalFilasEnData = count($tramo['pagos']);
            $filaInicioData = $filaInicial + 5;
            $ultimaFilaTramo = $filaInicioData + $totalFilasEnData - 1;

            $rows[] = [formatear_fecha($this->data['fecha_reporte'])];
            $rows[] = [$this->data['titulo']];
            $rows[] = ["SEMANA " . $index + 1];

            $periodo = CarbonPeriod::create(
                Carbon::parse($tramo['fecha_inicio']),
                Carbon::parse($tramo['fecha_fin'])
            )->toArray();

            // ---- ENCABEZADOS ----
            $header1 = ['N°', 'NOMBRES'];
            $header2 = ['', ''];

            foreach ($periodo as $fecha) {
                $header1[] = $fecha->format('d');
                $header2[] = mb_strtoupper($fecha->translatedFormat('l'));
            }

            $header1[] = 'MONTO';
            $header1[] = 'FIRMA';
            $header2[] = '';
            $header2[] = '';

            $rows[] = []; // espacio
            $rows[] = $header1;
            $rows[] = $header2;

            // ---- DATA ----
            $i = 1;
            foreach ($tramo['pagos'] as $pago) {
                $row = [$i, $pago['nombre']];
                foreach ($periodo as $fecha) {
                    $key = $fecha->toDateString();
                    $row[] = $pago[$key] ?? '';
                }
                $colStart = 3; // columna C (A=N°, B=Nombre, C=primer día)
                $colEnd   = $colStart + count($periodo) - 1;

                // convertir a letras
                $colLetterStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colStart);
                $colLetterEnd   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colEnd);

                // calcular número de fila actual en Excel
                $currentRow = $filaInicioData + $i - 1; // +1 porque Excel empieza en 1

                // fórmula horizontal de suma
                $formula = "=SUM({$colLetterStart}{$currentRow}:{$colLetterEnd}{$currentRow})";

                $row[] = $formula;
                $row[] = '';
                $rows[] = $row;
                $i++;
            }

            // ---- TOTALES ----
            $colIndex = 3;
            $rowTotales = ['', 'TOTAL'];

            foreach ($periodo as $fecha) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $formula = "=SUM({$colLetter}{$filaInicioData}:{$colLetter}{$ultimaFilaTramo})";
                $rowTotales[] = $formula;
                $colIndex++;
            }
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $formula = "=SUM({$colLetter}{$filaInicioData}:{$colLetter}{$ultimaFilaTramo})";
            $rowTotales[] = $formula;
            $rowTotales[] = '';
            $rows[] = $rowTotales;

            $rows[] = []; // espacio entre tramos
            $rows[] = [];

            $filaInicial = $ultimaFilaTramo + 2;
        }

        return $rows;
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnaInicial = 'A';
                $filaInicial = 1;

                foreach ($this->data['tramos'] as $index => $tramo) {

                    $periodo = CarbonPeriod::create(
                        Carbon::parse($tramo['fecha_inicio']),
                        Carbon::parse($tramo['fecha_fin'])
                    )->toArray();

                    // ---- Titulo principal merge dinámico ----
                    $totalCols = 2 + count($periodo) + 2;
                    $totalCol = $sheet->getCellByColumnAndRow($totalCols - 1, 1)->getColumn();
                    $lastCol = $sheet->getCellByColumnAndRow($totalCols, 1)->getColumn();

                    // Merge de fecha
                    $sheet->mergeCells("A{$filaInicial}:{$lastCol}{$filaInicial}");

                    // Merge de título principal
                    $sheet->mergeCells("A" . ($filaInicial + 1) . ":{$lastCol}" . ($filaInicial + 1));

                    // Merge de subtitulo
                    $sheet->mergeCells("A" . ($filaInicial + 2) . ":{$lastCol}" . ($filaInicial + 2));

                    // Merge encabezados
                    $sheet->mergeCells("A" . ($filaInicial + 3) . ":A" . ($filaInicial + 4));
                    $sheet->mergeCells("B" . ($filaInicial + 3) . ":B" . ($filaInicial + 4));
                    $sheet->mergeCells("{$totalCol}" . ($filaInicial + 3) . ":{$totalCol}" . ($filaInicial + 4));
                    $sheet->mergeCells("{$lastCol}" . ($filaInicial + 3) . ":{$lastCol}" . ($filaInicial + 4));
                    $filasData = count($tramo['pagos']);
                    $filaInicioData = $filaInicial + 5;
                    $ultimaFilaTramo = $filaInicioData + $filasData; // 4 filas de header + data
    

                    //estilo
                    //HEADER FECHA
                    $sheet->getStyle("A{$filaInicial}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 14,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                    ]);
                    //HEADER TITULO
                    $sheet->getStyle("A" . ($filaInicial + 1))->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 14,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_BOTTOM,
                        ],
                    ]);
                    $sheet->getStyle("A" . ($filaInicial + 2))->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_TOP,
                        ],
                    ]);
                    $sheet->getRowDimension(($filaInicial + 1))->setRowHeight(20);
                    $sheet->getRowDimension(($filaInicial + 2))->setRowHeight(20);

                    // ---- Estilo encabezados ----
                    $sheet->getStyle("A" . ($filaInicial + 3) . ":{$lastCol}" . ($filaInicial + 4))->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE5E5E5'],
                        ],
                    ]);

                    //$lastRow = $filasData + 6;
                    // Estilo footer
                    $sheet->getStyle("A{$ultimaFilaTramo}:{$lastCol}{$ultimaFilaTramo}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            //'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE5E5E5'],
                        ],
                    ]);

                    // ---- Estilo data ----
                    $sheet->getStyle("A{$filaInicioData}:{$lastCol}{$ultimaFilaTramo}")->applyFromArray([
                        //'font' => ['bold' => true],
                        'alignment' => [
                            //'horizontal' => Alignment::HORIZONTAL_RIGHT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            //'startColor' => ['argb' => 'FFE5E5E5'],
                        ],
                    ]);

                    $sheet->getStyle("A{$filaInicioData}:A{$ultimaFilaTramo}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ]
                    ]);

                    $sheet->getStyle("C{$filaInicioData}:{$totalCol}{$ultimaFilaTramo}")
                        ->getNumberFormat()
                        ->setFormatCode('"S/ "#,##0.00');

                    $totalAncho = 100 / 900; // factor de conversión px → Excel
                    $totalAlto = 100 / 166;
                    $wNumero = 30;
                    $wNombres = 304;
                    $wMontos = 111;
                    $wMontoTotal = 121;
                    $wFirma = 187;
                    $altura = 40 * $totalAlto;

                    // Columna A → Número
                    $sheet->getColumnDimension('A')->setWidth($wNumero * $totalAncho);

                    // Columna B → Nombres
                    //$sheet->getColumnDimension('B')->setWidth($wNombres * $totalAncho);
                    $sheet->getColumnDimension('B')->setAutoSize(true);

                    // Columnas dinámicas de días (C hasta …)
                    $colIndex = 'C';
                    foreach ($periodo as $i => $fecha) {
                        $sheet->getColumnDimension($colIndex)->setAutoSize(true);

                        $colIndex++;
                    }

                    for ($i = $filaInicioData; $i < $ultimaFilaTramo; $i++) {
                        $sheet->getRowDimension($i)->setRowHeight($altura);
                    }

                    // Últimas columnas (después del rango de fechas)
                    $sheet->getColumnDimension($colIndex)->setWidth($wMontoTotal * $totalAncho);
                    $colIndex++;
                    $sheet->getColumnDimension($colIndex)->setWidth($wFirma * $totalAncho);

                    //////////////////////////////////////////////////////////////////////////////////////////
                    // Poner un salto de página al final de este tramo
                    $sheet->setBreak("A{$ultimaFilaTramo}", Worksheet::BREAK_ROW);

                    // Actualizar fila inicial para el siguiente tramo (dejando 2 filas de espacio, opcional)
                    $filaInicial = $ultimaFilaTramo + 1;
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

                // tamaño de hoja A4 (puedes cambiar a LETTER, LEGAL, etc.)
                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                // Ajustar a 1 página de ancho y automático en alto
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.25);
                $sheet->getPageMargins()->setBottom(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setRight(0.25);

                $sheet->getPageSetup()->setHorizontalCentered(true);
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}
