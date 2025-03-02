<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KardexProductoSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private $kardexLista;
    private $informacionHeader;
    private $esCombustible = false;

    public function __construct(array $data)
    {
        $this->kardexLista = $data['kardexLista'];
        $this->informacionHeader = $data['informacionHeader'];
        $this->esCombustible = $data['esCombustible'];
    }

    public function title(): string
    {
        return $this->informacionHeader['codigo_existencia'];
    }

    /**
     * Define el formato para las celdas.
     *
     * @param Worksheet $sheet
     * @return array
     */

    public function styles(Worksheet $sheet)
    {
        function calculateColumnWidthFromPixels($pixels)
        {
            return ($pixels) / 7;
        }
        // Ajustar anchos de columnas
        $columnWidthsInPixels = [
            'A' => 130, // 107 px
            'B' => 120, // 158 px
            'C' => 130, // 112 px
            'D' => 90, // 118 px
            'E' => 88, // 138 px
            'F' => 75, // 135 px
            'G' => 134, // 134 px
            'H' => 108, // 124 px
            'I' => 75,
            'J' => 70, // 110 px
            'K' => 134, // 134 px
            'L' => 108, // 115 px
            'M' => 75, // 134 px
            'N' => 110, // 134 px
            'O' => 108, // 134 px
        ];

        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

        $sheet->getRowDimension(14)->setRowHeight(40 / 1.325);
        $sheet->getRowDimension(15)->setRowHeight(40 / 1.325);
        $sheet->getRowDimension(16)->setRowHeight(40 / 1.325);

        // Título principal
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Arial',
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Encabezados del bloque de información
        $sheet->getStyle('A3:A11')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial',
            ],
        ]);

        // Encabezados de tabla
        $sheet->mergeCells('A14:D15');
        $sheet->getStyle('A14:D15')->applyFromArray([
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'font' => [
                'bold' => true,
            ],
        ]);
        $sheet->getStyle('A14:N16')->applyFromArray([
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => true
            ],
            'font' => [
                'size' => 10,
                'name' => 'Arial',
                'bold' => true,
            ],
        ]);

        $sheet->mergeCells('B9:C9');

        $sheet->mergeCells('E14:E16');
        $sheet->mergeCells('F14:H14');
        $sheet->mergeCells('I14:L14');
        $sheet->mergeCells('M14:O14');

        $sheet->mergeCells('F15:F16');
        $sheet->mergeCells('G15:G16');
        $sheet->mergeCells('H15:H16');

        $sheet->mergeCells('I15:I16');
        $sheet->mergeCells('J15:J16');
        $sheet->mergeCells('K15:K16');
        $sheet->mergeCells('L15:L16');

        $sheet->mergeCells('M15:M16');
        $sheet->mergeCells('N15:N16');
        $sheet->mergeCells('O15:O16');

        $centerStyle = [
            'horizontal' => 'center',
            'vertical' => 'center',
        ];

        $sheet->getStyle('A14:O16')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => $centerStyle,
        ]);

        // Bordes en toda la tabla
        $sheet->getStyle('A14:O' . (14 + count($this->kardexLista) + 2))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);

        $sheet->getStyle('A17:O' . (14 + count($this->kardexLista)))
            ->applyFromArray([
                'font' => [
                    'size' => 10,
                    'name' => 'Arial',
                ],
            ]);

        $sheet->getStyle('A17:E' . (16 + count($this->kardexLista)))
            ->applyFromArray([
                'alignment' => $centerStyle,
            ]);
        $sheet->getStyle('G17:G' . (16 + count($this->kardexLista)))
            ->applyFromArray([
                'alignment' => $centerStyle,
            ]);
        $sheet->getStyle('J17:J' . (16 + count($this->kardexLista)))
            ->applyFromArray([
                'alignment' => $centerStyle,
            ]);
            
        $sheet->getStyle('A17:A' . (16 + count($this->kardexLista)))
            ->getNumberFormat()
            ->setFormatCode('dd/mm/yyyy');

        $columnasIzquierda = ['H17:H', 'K17:K', 'L17:L','N17:N', 'O17:O'];
        foreach ($columnasIzquierda as $cordenada) {
            $range = $cordenada . (16 + count($this->kardexLista)); // Rango dinámico para cada columna
            $sheet->getStyle($range)
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'right',
                        'vertical' => 'center',
                    ],
                ])
                ->getNumberFormat()
                ->setFormatCode('#,##0.00;-#,##0.00;"-"'); // Formato para precios
        }

        $columnasIzquierda = ['F17:F','I17:I', 'M17:M'];
        foreach ($columnasIzquierda as $cordenada) {
            $range = $cordenada . (16 + count($this->kardexLista)); // Rango dinámico para cada columna
            $sheet->getStyle($range)
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'right',
                        'vertical' => 'center',
                    ],
                ])
                ->getNumberFormat()
                ->setFormatCode('#,##0.000;-#,##0.000;"-"'); // Formato para precios
        }

        $sheet->setSelectedCell('A1');

        $sheet->getStyle('B9:C9')->applyFromArray([
            'font' => [
                'bold' => true,
                'name' => 'Arial',
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00', // Amarillo
                ],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, // Borde grueso
                    'color' => ['rgb' => '000000'], // Negro
                ],
            ],
        ]);

        return [];
    }

    /**
     * Devuelve los encabezados de la tabla.
     *
     * @return array
     */
    public function headings(): array
    {
        $campoDescripcion = $this->esCombustible?'MAQUINARIA':'LOTE';

        return [
            ['FORMATO 13.1: "REGISTRO DE INVENTARIO PERMANENTE VALORIZADO - DETALLE DEL INVENTARIO VALORIZADO"'],
            [],
            ['PERÍODO: ' . $this->informacionHeader['periodo']],
            ['RUC: ' . $this->informacionHeader['ruc']],
            ['APELLIDOS Y NOMBRES, DENOMINACIÓN O RAZÓN SOCIAL: ' . $this->informacionHeader['razon_social']],
            ['ESTABLECIMIENTO (1): ' . $this->informacionHeader['establecimiento']],
            ['CÓDIGO DE LA EXISTENCIA: ' . $this->informacionHeader['codigo_existencia']],
            ['TIPO (TABLA 5): ' . $this->informacionHeader['tipo']],
            ['DESCRIPCIÓN: ', $this->informacionHeader['descripcion']],
            ['CÓDIGO DE LA UNIDAD DE MEDIDA (TABLA 6): ' . $this->informacionHeader['codigo_unidad_medida']],
            ['MÉTODO DE VALUACIÓN: ' . $this->informacionHeader['metodo_valuacion']],
            [],
            [],
            [
                "DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO,\n DOCUMENTO INTERNO O SIMILAR",
                '',
                '',
                '',
                "TIPO DE\nOPERACIÓN\n(TABLA 12)",
                'ENTRADAS',
                '',
                '',
                'SALIDAS',
                '',
                '',
                '',
                'SALDO FINAL',
                '',
                ''
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'CANTIDAD',
                'COSTO UNITARIO',
                'COSTO TOTAL',
                'CANTIDAD',
                $campoDescripcion,
                'COSTO UNITARIO',
                'COSTO TOTAL',
                'CANTIDAD',
                'COSTO UNITARIO',
                'COSTO TOTAL'
            ],
            [
                'FECHA',
                'TIPO (TABLA 10)',
                'SERIE',
                'NÚMERO',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    /**
     * Devuelve los datos de la hoja en formato de arreglo.
     *
     * @return array
     */
    public function array(): array
    {
        // Número de filas de encabezado antes de los datos (ajusta este valor según tu diseño)
        $headerRows = 16;

        return array_map(function ($item, $index) use ($headerRows) {
            $rowIndex = $headerRows + $index + 1; // Índice real de la fila en Excel
            $rowIndexAnterior = $headerRows + $index;


            return [
                $item['fecha'] ?? '',

                $item['tabla10'] ?? '',
                $item['serie'] ?? '',
                $item['numero'] ?? '',
                $item['tipo_operacion'] ?? '',

                $item['entrada_cantidad'] ?? 0,
                "=IFERROR(H{$rowIndex}/F{$rowIndex}, \"\")",
                $item['entrada_costo_total'] ?? 0,

                $item['salida_cantidad'] ?? 0,
                $item['salida_lote'],
                "=N{$rowIndexAnterior}",
                "=I{$rowIndex}*K{$rowIndex}",

                "=(F{$rowIndex}+M{$rowIndexAnterior})-I{$rowIndex}",
                "=IF(M{$rowIndex}>0,O{$rowIndex}/M{$rowIndex},0)",
                "=(O{$rowIndexAnterior}+H{$rowIndex})-L{$rowIndex}",
            ];
        }, $this->kardexLista, array_keys($this->kardexLista));
    }

}
