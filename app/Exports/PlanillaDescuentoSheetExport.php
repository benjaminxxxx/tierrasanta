<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanillaDescuentoSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return "DESCUENTOS_AFP";
    }

    public function styles(Worksheet $sheet)
    {
        // Ajustar anchos de columnas
        $columnWidthsInPixels = [
            'A' => 96,
            'B' => 80,
            'C' => 80,
        ];

        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

        $sheet->getRowDimension(1)->setRowHeight(40 / 1.325);

        $centerStyle = [
            'horizontal' => 'center',
            'vertical' => 'center',
            'wrapText' => true,
        ];

        $sheet->getStyle("A1:C10")
            ->applyFromArray([
                'font' => [
                    'size' => 9,
                    'name' => 'Arial',
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
                'alignment' => $centerStyle,
            ]);


        $sheet->getStyle("B2:C10")
            ->getNumberFormat()
            ->setFormatCode('0.00%');

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
            ]
        ]);
        $sheet->getStyle('A2:A10')->applyFromArray([
            'font' => [
                'bold' => true,
            ]
        ]);

        $contador = 1;
        foreach ($this->data as $data) {
            $color = ltrim($data['descuento_sp']['color'], '#'); // Eliminar "#" si existe
            $contador++;
            $sheet->getStyle("A{$contador}:C{$contador}")->applyFromArray([
                'font' => [
                    'color' => ['rgb' => $color], // Solo RGB sin "#"
                ],
            ]);
        }

        return [];
    }

    /**
     * Devuelve los encabezados de la tabla.
     *
     * @return array
     */
    public function headings(): array
    {
        return [

            [
                "DESCUENTO",
                "%",
                "%>65"
            ]

        ];
    }

    /**
     * Devuelve los datos de la hoja en formato de arreglo.
     *
     * @return array
     */
    public function array(): array
    {
        return array_map(function ($item, $index) {

            return [
                $item['descuento_codigo'] ?? '',
                $item['porcentaje'] / 100 ?? '',
                $item['porcentaje_65'] / 100 ?? '',

            ];
        }, $this->data, array_keys($this->data));
    }
}
