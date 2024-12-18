<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanillaSheetExport2 implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private $planillaLista;
    private $mes;
    private $mesCadena;
    private $anio;
    private $diasLaborables;
    private $dias;
    private $factorRemuneracionBasica;
    private $filaInicial;
    private $ctsPorcentaje;
    private $data;

    public function __construct(array $data)
    {
        $this->planillaLista = $data['empleados'];
        $this->diasLaborables = $data['diasLaborables'];
        $this->factorRemuneracionBasica = $data['factorRemuneracionBasica'];
        $this->filaInicial = 7;
        $this->ctsPorcentaje = $data['ctsPorcentaje'];
        $this->data = $data;

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
        return "PLANILLA";
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
            'A' => 20,
            'B' => 70,
            'C' => 230,
            'D' => 43,
            'E' => 54, // 118 px
            'F' => 65, // 138 px
            'G' => 54, // 135 px
            'H' => 59, // 134 px
            'I' => 59, // 124 px
            'J' => 65,
            'K' => 65, // 110 px
            'L' => 65, // 134 px
            'M' => 65, // 115 px
            'N' => 65, // 134 px
            'O' => 65, // 134 px
            'P' => 65,
            'Q' => 65,
            'R' => 65,
            'T' => 65,
            'U' => 65,
            'V' => 65,
            'W' => 65,
            'X' => 65,

            'Y' => 10,
            'Z' => 10,
            'AA' => 30,
            'AB' => 230,
            'AC' => 73,
            'AD' => 73,
            'AE' => 73,
            'AF' => 73,
            'AG' => 73,
            'AH' => 25,
            'AI' => 73,
            'AJ' => 73,
            'AK' => 40,
        ];


        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

        $sheet->getRowDimension(4)->setRowHeight(40 / 1.325);

        $centerStyle = [
            'horizontal' => 'center',
            'vertical' => 'center',
            'wrapText' => true,
        ];

        $mergeCells = [
            "V1:X1",
            "V2:X2",
            "V3:X3",

            "A4:X4",

            "A5:A6",
            "B5:B6",
            "C5:C6",
            "D5:D6",
            "E5:E6",

            "F5:I5",

            "J5:J6",
            "K5:K6",
            "L5:L6",
            "M5:M6",
            "N5:N6",
            "O5:O6",
            "P5:P6",
            "Q5:Q6",
            "R5:R6",
            "S5:S6",
            "T5:T6",
            "U5:U6",
            "V5:V6",
            "W5:W6",
            "X5:X6",

            "AA5:AA6",
            "AB5:AB6",
            "AC5:AC6",
            "AD5:AD6",
            "AE5:AE6",
            "AF5:AF6",
            "AG5:AG6",

            "AI5:AI6",
            "AJ5:AJ6",
            "AK5:AK6",
        ];



        foreach ($mergeCells as $mergeCell) {
            $sheet->mergeCells($mergeCell);
        }

        $centerCells = ["A", "B", "D", "E", "F", "L", "M", "N", "O", "P", "Q", "R", "S", "AA", "AC", "AD", "AE", "AF", "AG", "AI", "AJ", "AK"];
        foreach ($centerCells as $centerCell) {
            $sheet->getStyle("{$centerCell}{$this->filaInicial}:{$centerCell}" . ($this->filaInicial + count($this->planillaLista)))
                ->applyFromArray([
                    'alignment' => $centerStyle,
                ]);
        }

        $rightCells = [
            'G',
            'H',
            'I',
            'J',
            'K',
            'H',
            'V',
            'W',
            'X'
        ];

        foreach ($rightCells as $rightCell) {
            $sheet->getStyle("{$rightCell}{$this->filaInicial}:{$rightCell}" . ($this->filaInicial + count($this->planillaLista)))
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'right',
                        'vertical' => 'center',
                    ],
                ]);
        }

        $sheet->getStyle("F{$this->filaInicial}:X" . ($this->filaInicial + count($this->planillaLista)))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00;-#,##0.00;"-"');

        $sheet->getStyle("AC{$this->filaInicial}:AF" . ($this->filaInicial + count($this->planillaLista)))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00;-#,##0.00;"-"');

        $sheet->getStyle("AG{$this->filaInicial}:AG" . ($this->filaInicial + count($this->planillaLista)))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00000;-#,##0.00000;"-"');

        $sheet->getStyle("AI{$this->filaInicial}:AJ" . ($this->filaInicial + count($this->planillaLista)))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00;-#,##0.00;"-"');

        $sheet->getStyle('A4:AN6')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => $centerStyle,
        ]);


        // Bordes en toda la tabla
        $sheet->getStyle('U1:U3')
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'alignment' => $centerStyle,
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);

        $sheet->getStyle('A5:X' . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);
        $sheet->getStyle('AA5:AG' . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);
        $sheet->getStyle('AI5:AK' . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ]
            ]);

        $sheet->getStyle("A{$this->filaInicial}:AK" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'size' => 9,
                    'name' => 'Arial',
                ],
            ]);

        $sheet->getStyle("P{$this->filaInicial}:Q" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
            ]);

        $sheet->getStyle("U{$this->filaInicial}:U" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FF0000'],
                ],
            ]);
        $sheet->getStyle("AD{$this->filaInicial}:AD" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '00B050'],
                ],
            ]);
        $sheet->getStyle("AF{$this->filaInicial}:AF" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0070C0'],
                ],
            ]);
        $sheet->getStyle("W{$this->filaInicial}:W" . (4 + count($this->planillaLista) + 2))
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0070C0'],
                ],
            ]);

        $sheet->getStyle('A4:S4')->applyFromArray([
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

        $sheet->getStyle('F5:I6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00', // Color amarillo
                ],
            ],
        ]);

        /*****************************COLORES POR EMPELADO ***/
        $contadorp = $this->filaInicial - 1;
        foreach ($this->planillaLista as $planilla) {
            $color = ltrim($planilla['color'], '#'); // Eliminar "#" si existe
            $contadorp++;
            $sheet->getStyle("E{$contadorp}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color], // Solo RGB sin "#"
                ],
            ]);
            $sheet->getStyle("K{$contadorp}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color], // Solo RGB sin "#"
                ],
            ]);
        }
        /*************************************************** */
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
                " ",
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
                "=U2*8",
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
                "",
                "",
                $this->diasLaborables,
                "DÍAS LABORABLES"
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
                "",
                "",
                $this->dias,
                "DÍAS"
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
                "",
                "",
                "",
                ""
            ],
            [
                "Nº",
                "DNI",
                "NOMBRES",
                "EDAD",
                "SPP o SNP",
                "SUELDO BRUTO",
                "",
                "",
                "",
                "SUELDO BRUTO",
                "DSCTO. A.F.P.(Prima de Seguro",
                "CTS",
                "GRATIFICACIONES",
                "ESSALUD GRATIFICACIONES",
                "BETA 30 %",
                "ESSALUD",
                "VIDA LEY",
                "PENSION SCTR",
                "ESSALUD EPS",
                "SUELDO NETO",
                "REM. BASICA+ESSALUD",
                "(REM. BASICA+ASG.FAM. X(6%) ESSALUD)+CTS+GRATIF.+BETA",
                "JORNAL DIARIO",
                "COSTO HORA",
                "",
                "",
                "Nº",
                "NOMBRES",
                "DIFERENCIA O BONIFICACION",
                "SUELDO NETO TOTAL",
                "SUELDO BRUTO NEGRO",
                "SUELDO POR DIA",
                "SUELDO POR HORA",
                "",
                "DIFERENCIA POR HORA",
                "DIFERENCIA REAL",
                "ESTÁ JUBILADO",
            ],
            [
                "",
                "",
                "",
                "",
                "",
                "REMUNERACIÓN BÁSICA",
                "BONIF.",
                "ASIGNACION FAMILIAR",
                "COMPEN. VACACIONAL",

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
        $indiceInicio = $this->filaInicial;
        return array_map(function ($item, $index) use ($indiceInicio) {

            $f = $index + $indiceInicio;
            return [
                $index + 1, // Número secuencial, inicia en 1
                $item['dni'] ?? '',
                $item['nombres'] ?? '',
                $item['edad'] ?? '',
                $item['sppSnp'] ?? '',
                "={$this->factorRemuneracionBasica}*\$U\$3",
                $item['bonificacion'] ?? '',
                $item['asignacionFamiliar'] ?? '',
                $item['compensacionVacacional'] ?? '',
                "=F{$f}+G{$f}+H{$f}+I{$f}",
                "=J{$f}*(IF(Ak{$f}=\"SI\",0,IF(D{$f}>65,VLOOKUP(E{$f},DESCUENTOS_AFP!\$A\$2:\$C\$10,3,FALSE),VLOOKUP(E{$f},DESCUENTOS_AFP!\$A\$2:\$C\$10,2,FALSE))))",
                "=((F{$f}+G{$f}+H{$f})*({$this->ctsPorcentaje}%))", //CTS
                "=(F{$f}+G{$f}+H{$f})*({$this->data['gratificacionesPorcentaje']}%)", //GRATIFICACIONES
                "=M{$f}*{$this->data['essaludGratificacionesPorcentaje']}%", //ESSALUD
                "={$this->data['rmv']}*{$this->data['beta30Porcentaje']}%", //BETA 30 
                "=J{$f}*{$this->data['essaludPorcentaje']}%", // ESSALUD
                "=((J{$f}*({$this->data['vidaLeyPorcentaje']}%))*{$this->data['vidaLey']})", // VIDA LEY
                "=(J{$f}*({$this->data['pensionSctrPorcentaje']}%))*{$this->data['pensionSctr']}", // PENSION SCTR
                "=(J{$f}*({$this->data['essaludEpsPorcentaje']}%))*{$this->data['porcentajeConstante']}", // ESSALUD EPS
                "=(J{$f}-K{$f})+L{$f}+M{$f}+N{$f}+O{$f}", // SUELDO NETO
                "",
                "=J{$f}+L{$f}+M{$f}+N{$f}+O{$f}+P{$f}+Q{$f}+R{$f}+S{$f}", // (REM. BASICA+ASG.FAM. X(6%) ESSALUD)+CTS+GRATIF.+BETA
                "=V{$f}/\$U\$2", // JORNAL DIARIO
                "=+W{$f}/8", // COSTO HORA
                "",
                "",
                $index + 1,
                $item['nombres'] ?? '',
                "=AD{$f}-T{$f}",
                $item['sueldoPersonal'] ?? '',
                "=V{$f}+AC{$f}",
                "=AE{$f}/\$U\$2",
                "=AF{$f}/8",
                "",
                "=AC{$f}/\$U\$1",
                "",
                $item['estaJubilado'] ?? '',

            ];
        }, $this->planillaLista, array_keys($this->planillaLista));
    }
}
