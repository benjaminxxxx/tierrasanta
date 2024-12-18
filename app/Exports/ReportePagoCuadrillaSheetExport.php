<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportePagoCuadrillaSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private $cuadrilleros;
    private $informacionHeader;

    /**
     * Constructor de la clase
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->cuadrilleros = $data['cuadrilleros'];
        $this->informacionHeader = $data['informacionHeader'];
    }

    /**
     * Título de la hoja
     *
     * @return string
     */
    public function title(): string
    {
        return "CUADRILLA";
    }
    public function columnToLetter($col)
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr($col % 26 + 65) . $letter;
            $col = floor($col / 26);
        }
        return $letter;
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado principal
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Bordes para los datos
        $lastColumnIndex = 4 + count($this->generateDynamicHeadings()['weekdays']) + 2; // Índice de la última columna
        $lastColumn = $this->columnToLetter($lastColumnIndex); // Convierte el índice en la letra de columna

        $lastRow = count($this->cuadrilleros) + 11; // Ajustar según el número de filas
        $sheet->getStyle("A10:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Formato de moneda para montos
        $startColumnIndex = 4 + count($this->generateDynamicHeadings()['weekdays']);
        $startColumn = $this->columnToLetter($startColumnIndex); // Columna del total
        $montoColumn = $this->columnToLetter($startColumnIndex + 1); // Columna de monto pagado
        $estadoColumn = $this->columnToLetter($startColumnIndex + 2);

        $sheet->getStyle("D12:{$montoColumn}{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00;[Red]"S/" -#,##0.00;"S/" "-"');


        $sheet->mergeCells('A10:A11');
        $sheet->mergeCells('B10:B11');
        $sheet->mergeCells('C10:C11');
        $sheet->mergeCells("{$startColumn}10:{$startColumn}11");
        $sheet->mergeCells("{$montoColumn}10:{$montoColumn}11");
        $sheet->mergeCells("{$estadoColumn}10:{$estadoColumn}11");

        // Centrando columnas A, B y Total
        $sheet->getStyle("A10:A{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("B10:B{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $mergedCells = [
            'A10:A11',
            'B10:B11',
            'C10:C11',
            "{$startColumn}10:{$startColumn}11",
            "{$montoColumn}10:{$montoColumn}11",
            "{$estadoColumn}10:{$estadoColumn}{$lastRow}"
        ];

        foreach ($mergedCells as $range) {
            $sheet->getStyle($range)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->getStyle("A10:{$estadoColumn}11")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '056A70',
                ],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Encabezados de fechas centrados
        $fechaStartColumn = 'D';
        $sheet->getStyle("{$fechaStartColumn}10:{$montoColumn}11")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ancho de las columnas
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension($startColumn)->setWidth(12);
        $sheet->getColumnDimension($montoColumn)->setWidth(19);
        $sheet->getColumnDimension($estadoColumn)->setWidth(15);


        // Ajustar automáticamente las columnas de fechas
        for ($col = ord('D'); $col < $startColumnIndex; $col++) {
            $sheet->getColumnDimension(chr($col))->setAutoSize(true);
        }

        $lastRow = count($this->cuadrilleros) + 12;

        $startColumnIndex = 4 + count($this->generateDynamicHeadings()['weekdays']);
        $totalColumn = $this->columnToLetter($startColumnIndex); // Columna del total
        $montoColumn = $this->columnToLetter($startColumnIndex + 1);

        // Agregar las fórmulas de SUMA al final
        $sheet->setCellValue("{$totalColumn}{$lastRow}", "=SUM({$totalColumn}12:{$totalColumn}" . ($lastRow - 1) . ")");
        $sheet->setCellValue("{$montoColumn}{$lastRow}", "=SUM({$montoColumn}12:{$montoColumn}" . ($lastRow - 1) . ")");

        // Dar formato a la fila de totales
        $sheet->getStyle("{$totalColumn}{$lastRow}:{$montoColumn}{$lastRow}")
            ->getFont()->setBold(true);
        $sheet->getStyle("{$totalColumn}{$lastRow}:{$montoColumn}{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("{$totalColumn}{$lastRow}:{$montoColumn}{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00;[Red]"S/" -#,##0.00;"S/" "-"');

        return [];
    }


    /**
     * Devuelve los encabezados de la hoja
     *
     * @return array
     */
    public function headings(): array
    {
        $dynamicHeadings = $this->generateDynamicHeadings();

        return [
            ['REGISTRO DE CUADRILLEROS'],
            [],
            ['FECHA INICIAL: ' . $this->informacionHeader['fecha_inicio']->format('Y-m-d')],
            ['FECHA FINAL: ' . $this->informacionHeader['fecha_fin']->format('Y-m-d')],
            ['GRUPO: ' . $this->informacionHeader['grupo']],
            ['TOTAL DE REGISTROS: ' . $this->informacionHeader['total_registros']],
            ['TOTAL DE REGISTROS PAGADOS: ' . $this->informacionHeader['total_registros_pagados']],
            [],
            [],
            array_merge(
                ["N°", "GRUPO", "CUADRILLERO"],
                $dynamicHeadings['weekdays'],
                ["TOTAL", "MONTO PAGADO", "ESTADO"]
            ),
            array_merge(
                ["-", "-", "-"],
                $dynamicHeadings['days'],
                ["-", "-", "-"]
            )
        ];
    }

    private function generateDynamicHeadings(): array
    {
        $weekdays = [];
        $days = [];
        $start = Carbon::parse($this->informacionHeader['fecha_inicio']);
        $end = Carbon::parse($this->informacionHeader['fecha_fin']);

        while ($start->lte($end)) {
            $weekdays[] = mb_strtoupper(mb_substr($start->locale('es')->dayName, 0, 1)); // L, M, M, etc.
            $days[] = $start->day; // 2, 3, etc.
            $start->addDay();
        }

        return ['weekdays' => $weekdays, 'days' => $days];
    }
    /**
     * Devuelve los datos de la hoja en formato de arreglo
     *
     * @return array
     */
    public function array(): array
    {
        return array_map(function ($item, $index) {
            $dynamicData = $this->generateDynamicValues($item);

            return array_merge([
                $index + 1,
                $item['grupo_codigo'] ?? '',
                $item['empleado'] ?? ''
            ], $dynamicData, [
                $item['monto_total'] ?? '',
                $item['monto_pagado'] ?? '',
                $item['esta_cancelado'] ? 'Pagado' : '-'
            ]);
        }, $this->cuadrilleros, array_keys($this->cuadrilleros));
    }

    /**
     * Genera valores dinámicos para las fechas
     *
     * @param array $item
     * @return array
     */
    private function generateDynamicValues(array $item): array
    {
        $values = [];
        $start = Carbon::parse($this->informacionHeader['fecha_inicio']);
        $end = Carbon::parse($this->informacionHeader['fecha_fin']);

        while ($start->lte($end)) {
            $values[] = $item[$start->format('Y-m-d')] ?? ''; // Cambiar según cómo esté estructurada la data
            $start->addDay();
        }

        return $values;
    }
}
