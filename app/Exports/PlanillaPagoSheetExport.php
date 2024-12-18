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

class PlanillaPagoSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{

    private $planillaLista;
    private $empleados;
    private $mes;
    private $mesCadena;
    private $anio;
    private $dias;
    private $filaInicial;
    private $data;
    private $informacionAdicional;

    public function __construct(array $data)
    {
        $this->empleados = $data['horas']['empleados'];
        $this->filaInicial = 5;
        $this->data = $data;
        $this->planillaLista = $data['empleados'];
        $this->informacionAdicional = $data['horas']['informacionAsistenciaAdicional'];

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

        $this->mesCadena = $meses[$this->mes] ?? 'MES INVÁLIDO';
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
        return "PAGO";
    }

    public function styles(Worksheet $sheet)
    {
        $columnWidthsInPixels = [
            'A' => 20,
            'B' => 70,
            'C' => 233,
            'AI' => 80,
        ];


        foreach ($columnWidthsInPixels as $column => $pixels) {
            $sheet->getColumnDimension($column)->setWidth(calculateColumnWidthFromPixels($pixels));
        }

        for ($x = 4; $x <= 34; $x++) {
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


        /*FORMULAS */
        $filaInicio = $this->filaInicial; // Fila inicial del rango (e.g., 5)
        $filaFin = $filaInicio + count($this->planillaLista) - 1; // Fila final del rango
        $filaFinTotales = $filaFin + 1;
        $columnaInicio = 4; // Columna inicial (D = índice 4 en base 1)

        // Usar un foreach para recorrer los días y establecer fórmulas dinámicas

        foreach ($this->dias as $index => $dia) {
            // Calcular índice dinámico de la columna
            $columnaIndice = $columnaInicio + $index; // Índice dinámico de columna basado en días
            $columnaLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnaIndice);

            // Construir la fórmula para la celda
            $formula = "=COUNTIF({$columnaLetra}{$filaInicio}:{$columnaLetra}{$filaFin},\">7\")";
            $formulaTotal = "=SUM({$columnaLetra}{$filaInicio}:{$columnaLetra}{$filaFin})";

            // Establecer la fórmula en la fila 2 para la columna correspondiente
            $informacionAdicionalPorDia = $this->informacionAdicional['dia_' . $dia[0]];
            $sheet->setCellValue("{$columnaLetra}2", $formula);
            $sheet->setCellValue("{$columnaLetra}{$filaFinTotales}", $formulaTotal);

            $filaEmpleadoContador = $this->filaInicial - 1;
            foreach ($this->planillaLista as $planilla) {
                $filaEmpleadoContador++;
                $documento = $planilla['dni'];
                if (array_key_exists($documento, $informacionAdicionalPorDia)) {
                    $informacionEmpleado = $informacionAdicionalPorDia[$documento];
                    if (array_key_exists('color', $informacionEmpleado)) {
                        $informacionEmpleado = $informacionAdicionalPorDia[$documento];

                        $color = ltrim($informacionEmpleado['color'], '#');
                        $tipoAsistencia = $informacionEmpleado['tipo_asistencia'];
                        if ($tipoAsistencia == 'F') {
                            $sheet->setCellValue("{$columnaLetra}{$filaEmpleadoContador}", 'F');
                        }


                        $sheet->getStyle("{$columnaLetra}{$filaEmpleadoContador}")
                            ->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, // Tipo de relleno
                                    'startColor' => [
                                        'rgb' => $color, // Color de fondo
                                    ],
                                ],
                            ]);
                    }
                }
            }
        }
        /******** */

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
                "PLANILLA ESPECIAL TIERRA SANTA HOLDING SAC  - {$this->mesCadena} {$this->anio}",
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
            $indiceReferencia = $f+2;
            //02170003
            $empleadoBuscados = array_filter($this->empleados, function ($empleado) use ($item) {

                return str_pad($empleado['documento'], 8, '0', STR_PAD_LEFT) === str_pad($item['dni'], 8, '0', STR_PAD_LEFT);
            });
            $empleado = [];
            if (count($empleadoBuscados) > 0) {

                $empleado = reset($empleadoBuscados);

                $resultado = [
                    $index + 1, // Número secuencial, inicia en 1
                    $empleado['grupo'] ?? '',
                    $item['nombres'] ?? '',
                ];

                // Agregar dinámicamente los días desde dia_1 hasta dia_último_dia_del_mes
                foreach ($this->dias as $dia) {

                    $columnaIndice = $dia[0]+3;
                    $columnaLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnaIndice);
                    $resultado[] = "=IF(HORAS!{$columnaLetra}{$f}=\"F\",0,PLANILLA!\$AG{$indiceReferencia}*HORAS!{$columnaLetra}{$f})";

                }
                $diasActuales = count($this->dias);
                $diasFaltantes = 31 - $diasActuales; // Calcular cuántas celdas faltan
                for ($i = 0; $i < $diasFaltantes; $i++) {
                    $resultado[] = ""; // Agregar celdas vacías
                }
                $resultado[] = "=SUM(D{$f}:AH{$f})";
                
                return $resultado;
            } else {
                return [
                    $index + 1, // Número secuencial, inicia en 1
                    '',
                    $item['nombres'] ?? '',
                ];
            }
        }, $this->planillaLista, array_keys($this->planillaLista));
    }
}
