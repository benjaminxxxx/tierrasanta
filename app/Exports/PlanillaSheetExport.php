<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanillaSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private $planillaLista;
    private $mes;
    private $mesCadena;
    private $anio;
    private $diasLaborables;
    private $dias;
    private $factorRemuneracionBasica;

    public function __construct(array $data)
    {
        $this->planillaLista = $data['empleados'];
        $this->diasLaborables = $data['diasLaborables'];
        $this->factorRemuneracionBasica = $data['factorRemuneracionBasica'];


        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE'
        ];

        $this->mes = $data['mes'];
        $this->anio = $data['anio'];

        $this->dias = Carbon::create($this->anio, $this->mes)->daysInMonth;
        $this->mesCadena = $meses[$this->mes] ?? 'MES INVÁLIDO';
    }

    public function title(): string
    {
        return $this->mesCadena;
    }

    /**
     * Define el formato para las celdas.
     *
     * @param Worksheet $sheet
     * @return array
     */

    public function styles(Worksheet $sheet)
    {
        /*
        function calculateColumnWidthFromPixels($pixels)
        {
            return ($pixels) / 7;
        }
        // Ajustar anchos de columnas
        $columnWidthsInPixels = [
            'A' => 30,
            'B' => 95, // 158 px
            'C' => 240, // 112 px
            'D' => 80, // 118 px
            'E' => 75, // 138 px
            'F' => 75, // 135 px
            'G' => 80, // 134 px
            'H' => 80, // 124 px
            'I' => 75,
            'J' => 75, // 110 px
            'K' => 70, // 134 px
            'L' => 70, // 115 px
            'M' => 70, // 134 px
            'N' => 70, // 134 px
            'O' => 70,
            'P' => 70,
            'Q' => 70,
            'R' => 75,
            'S' => 75,
            'T' => 75,
            'U' => 75,
            'V' => 75,
        ];

        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

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

        $centerStyle = [
            'horizontal' => 'center',
            'vertical' => 'center',
        ];

        $mergeCells = ["A6:A7", "B6:B7", "C6:C7", "D6:D7", "E6:E7", "F6:F7", "J6:J7", "K6:K7", "L6:L7", "M6:M7", "N6:N7", "O6:O7", "U6:U7", "V6:V7", "A4:R4"];
        $centerCells = ["A8:A", "B8:B", "D8:D", "J8:J", "K8:K", "L8:L", "M8:M", "O8:O", "P8:P", "Q8:Q"];
        $rightCells = [
            'E8:E',
            'F8:F',
            'G8:G',
            'H8:H',
            'I8:I',
            'R8:R',
            'S8:S',
            'T8:T',
            'U8:U',
            'V8:V',
        ];

        foreach ($mergeCells as $mergeCell) {
            $sheet->mergeCells($mergeCell);
        }
        foreach ($centerCells as $centerCell) {
            $sheet->getStyle($centerCell . (8 + count($this->planillaLista)))
                ->applyFromArray([
                    'alignment' => $centerStyle,
                ]);
        }
        foreach ($rightCells as $rightCell) {
            $range = $rightCell . (16 + count($this->planillaLista)); // Rango dinámico para cada columna
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

        $sheet->getStyle('A4:AN7')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => $centerStyle,
        ]);

        // Bordes en toda la tabla
        $sheet->getStyle('A4:V' . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);

        $sheet->getStyle('A8:V' . (4 + count($this->planillaLista)))
            ->applyFromArray([
                'font' => [
                    'size' => 9,
                    'name' => 'Arial',
                ],
            ]);



        $sheet->getStyle('A4:R4')->applyFromArray([
            'font' => [
                'bold' => true,
                'name' => 'Arial',
                'size' => 14,
                'color' => ['rgb' => 'FF0000'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ]
        ]);

        $sheet->getStyle('D5:G7')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00', // Color amarillo
                ],
            ],
        ]);*/

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
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                ""
            ],
            [
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "=S3*8",
                "HORAS"
            ],
            [
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                $this->diasLaborables,
                "DÍAS LABORABLES"
            ],
            [
                "MES DE {$this->mesCadena} {$this->anio}",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                $this->dias,
                "DÍAS"
            ],
            [
                "",
                "",
                "",
                "SUELDO BRUTO",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                ""
            ],
            [
                "Nº",
                "NOMBRES",
                "SPP o SNP",
                "REMUNERACIÓN BÁSICA",
                "BONIF.",
                "ASIGNACION FAMILIAR",
                "COMPEN.",
                "SUELDO",
                "DSCTO.",
                "CTS",
                "GRATIFICACIONES",
                "ESSALUD GRATIFICACIONES",
                "BETA 30 %",
                "ESSALUD",
                "VIDA LEY",
                "PENSION",
                "ESSALUD",
                "SUELDO",
                "REM. BASICA+ESSALUD",
                "(REM. BASICA+ASG.FAM. X(6%) ESSALUD)+CTS+GRATIF.+BETA",
                "JORNAL DIARIO",
                "COSTO HORA"
            ],
            [
                "",
                "",
                "",
                "",
                "",
                "",
                "VACACIONAL",
                "BRUTO",
                "A.F.P.(Prima de Seguro",
                "",
                "",
                "",
                "",
                "",
                "",
                "SCTR",
                "EPS",
                "NETO",
                "",
                ""
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
        $f = 6;
        return array_map(function ($item, $index) use ($f) {
            return [
                $index + 1, // Número secuencial, inicia en 1
                $item['dni'] ?? '',
                $item['nombres'] ?? '',
                $item['sppSnp'] ?? '',
                "={$this->factorRemuneracionBasica}*\$S\$4",
                $item['bonificacion'] ?? '',
                $item['asignacionFamiliar'] ?? '',
                $item['compensacionVacacional'] ?? '',
                "=D{$f}+E{$f}+F{$f}+G{$f}",
                "=H{$f}*{$item['descuentoSeguro']['descuento']}",
            ];
        }, $this->planillaLista, array_keys($this->planillaLista));
    }
}
