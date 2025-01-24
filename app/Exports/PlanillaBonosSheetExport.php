<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class PlanillaBonosSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{

    private $planillaLista;
    private $bonos;
    private $mes;
    private $mesCadena;
    private $anio;
    private $dias;
    private $filaInicial;

    public function __construct(array $data)
    {
        $this->bonos = $data['bonos'];
        $this->filaInicial = 5;
        $this->planillaLista = $data['empleados'];

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

        $this->dias = $this->generarDiasDelMes($this->mes, $this->anio);

        $this->mesCadena = $meses[(int)$this->mes] ?? 'MES INVÁLIDO';
    }
    public function generarDiasDelMes($mes, $anio)
    {
        $diasDelMes = [];
        $diasSemana = ["D", "L", "M", "M", "J", "V", "S"]; // Abreviaturas, donde M representa Martes y Miércoles

        // Crear una instancia de Carbon para el primer día del mes
        $fecha = Carbon::create($anio, $mes, 1);
        $diasEnMes = $fecha->daysInMonth;

        // Iterar por cada día del mes
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $abreviatura = $diasSemana[$fecha->dayOfWeek]; // Obtener el índice del día de la semana
            $diasDelMes[] = [$dia, $abreviatura];
            $fecha->addDay(); // Avanzar al siguiente día
        }
        return $diasDelMes;
    }
    public function title(): string
    {
        return "BONOS";
    }

    public function styles(Worksheet $sheet)
    {
        $columnWidthsInPixels = [
            'A' => 20,
            'B' => 70,
            'C' => 233,
        ];


        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

        for ($x = 4; $x <= 35; $x++) {
            $sheet->getColumnDimensionByColumn($x)->setWidth(calculateColumnWidthFromPixels(40));
        }

        $sheet->getRowDimension(1)->setRowHeight(40 / 1.325);

        $centerStyle = [
            'horizontal' => 'center',
            'vertical' => 'center',
            'wrapText' => true,
        ];

        $mergeCells = [
            "A1:Ai1",
            "A3:A4",
            "B3:B4",
            "C3:C4",
            "AI3:AI4"
        ];
        foreach ($mergeCells as $mergeCell) {
            $sheet->mergeCells($mergeCell);
        }

        $centerCells = ["A"];
        foreach ($centerCells as $centerCell) {
            $sheet->getStyle("{$centerCell}{$this->filaInicial}:{$centerCell}" . ($this->filaInicial + count($this->planillaLista)))
                ->applyFromArray([
                    'alignment' => $centerStyle,
                ]);
        }
        $sheet->getStyle("D{$this->filaInicial}:AI" . ($this->filaInicial + count($this->planillaLista)))
            ->applyFromArray([
                'alignment' => $centerStyle,
            ]);


        $sheet->getStyle('A2:AI4')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => $centerStyle,
        ]);


        // Bordes en toda la tabla       

        $sheet->getStyle('A3:AI' . (4 + count($this->planillaLista)))
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
                ]
            ]);




        $sheet->getStyle("A2:AI2")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FF0000'],
                ],
            ]);

        $sheet->getStyle("A1:AI1")
            ->applyFromArray([
                'font' => [
                    'size' => 14,
                    'bold' => true,
                    'color' => ['rgb' => '990099'],
                ],
                'alignment' => $centerStyle,
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
        $heading = [
            [
                "PLANILLA ESPECIAL TIERRA SANTA HOLDING SAC  - {$this->mesCadena} {$this->anio} - BONOS",
            ],
            [
                "",
                "",
                "Recuento personas trabajando",
            ]
        ];
        $linea1 = [
            "Nº",
            "GRUPO",
            "NOMBRES",
        ];
        $linea2 = [
            "",
            "",
            "",
        ];
        foreach ($this->dias as $dia) {
            $linea1[] = $dia[1];
            $linea2[] = $dia[0];
        }

        $diasActuales = count($this->dias);
        $diasFaltantes = 31 - $diasActuales; // Calcular cuántas celdas faltan
        for ($i = 0; $i < $diasFaltantes; $i++) {
            $linea1[] = ""; // Agregar celdas vacías
        }

        $linea1[] = "HORAS";

        $heading[2] = $linea1;
        $heading[3] = $linea2;

        return $heading;
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
            //02170003
            $resultado = [
                $index + 1, // Número secuencial, inicia en 1
                "=HORAS!B{$f}",
                $item['nombres'] ?? '',
            ];

            // Agregar dinámicamente los días desde dia_1 hasta dia_último_dia_del_mes
            foreach ($this->dias as $dia) {
                $claveDia = "dia_" . $dia[0];
                $resultado[] = $this->bonos[$item['dni']][$claveDia] ?? null; // Agregar valor del día o null si no existe
            }

            $diasActuales = count($this->dias);
            $diasFaltantes = 31 - $diasActuales; // Calcular cuántas celdas faltan
            for ($i = 0; $i < $diasFaltantes; $i++) {
                $resultado[] = ""; // Agregar celdas vacías
            }
            $resultado[] = "=SUM(D{$f}:AH{$f})";

            return $resultado;
        
        }, $this->planillaLista, array_keys($this->planillaLista));
    }
}
