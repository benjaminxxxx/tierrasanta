<?php

namespace App\Exports\Produccion\MateriaPrima;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PoblacionPlantaExport implements FromArray, WithEvents, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data; // contiene filtros y datos
    }

    public function title(): string
    {
        return 'POBLACION PLANTAS';
    }


    public function array(): array
    {
        // Excel necesita al menos una fila para iniciar
        return [
            [''],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;
                $filtros = $this->data['filtros'];
                $datos = $this->data['datos'];

                //------------------------------------------------------------
                // FILA 1 – TÍTULO
                //------------------------------------------------------------
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'EVALUACION POBLACION DE PLANTAS');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ]
                ]);

                //------------------------------------------------------------
                // FILA 2 – RESPETO (vacía)
                //------------------------------------------------------------
    
                //------------------------------------------------------------
                // FILAS 3–6  → FILTROS
                //------------------------------------------------------------
                $map = [
                    'campo' => 'CAMPO',
                    'campania' => 'CAMPAÑA',
                    'evaluador' => 'EVALUADOR',
                    'fecha' => 'FECHA',
                ];

                $fila = 3;

                foreach ($map as $key => $label) {

                    // columna A–C → label
                    $sheet->mergeCells("A{$fila}:C{$fila}");
                    $sheet->setCellValue("A{$fila}", $label);

                    // columna D–H → valor
                    $sheet->mergeCells("D{$fila}:H{$fila}");
                    $sheet->setCellValue("D{$fila}", $filtros[$key] ?: '-');

                    // estilizado básico
                    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            'vertical' => 'center',
                        ]
                    ]);

                    $fila++;
                }

                // Este es el punto donde arrancan las tablas ↓
                $filaInicioCampo = 8;
                $altoBloqueCampo = 21;
                $anchoBloqueCampania = 9;

                //-----------------------------------------
                // RECORRER CAMPOS (vertical)
                //-----------------------------------------
                foreach ($datos as $campo => $campanias) {

                    // Flag horizontal se reinicia para cada campo
                    $columnaInicioCampania = 1; // A
    
                    foreach ($campanias as $campania => $info) {

                        $mapPorCartilla = [
                            'fecha_siembra' => 'FECHA SIEMBRA',
                            'evaluador' => 'EVALUADOR',
                            'metros_cama_ha' => 'METROS DE CAMA/HA',
                            'campania' => 'CAMPAÑA',
                        ];

                        $filaFiltro = $filaInicioCampo;
                        $metrosCamaHaRow = null;
                        // Por cada fila de información (4 filas)
                        foreach ($mapPorCartilla as $indiceInfo => $etiquetaTexto) {

                            // Cols A–C = etiqueta
                            $colIniEtiqueta = $this->col($columnaInicioCampania);
                            $colFinEtiqueta = $this->col($columnaInicioCampania + 2);

                            // Cols D–H = valor
                            $colIniValor = $this->col($columnaInicioCampania + 3);
                            $colFinValor = $this->col($columnaInicioCampania + 7);

                            // Obtener valor real
                            $valor = $indiceInfo === 'campania'
                                ? $campania
                                : ($info[$indiceInfo] ?? '-');

                            // MERGE etiqueta
                            $sheet->mergeCells("{$colIniEtiqueta}{$filaFiltro}:{$colFinEtiqueta}{$filaFiltro}");
                            $sheet->setCellValue("{$colIniEtiqueta}{$filaFiltro}", $etiquetaTexto);

                            // MERGE valor
                            $sheet->mergeCells("{$colIniValor}{$filaFiltro}:{$colFinValor}{$filaFiltro}");
                            $sheet->setCellValue("{$colIniValor}{$filaFiltro}", $valor);

                            // Estilo etiqueta (negrita + alineación)
                            $sheet->getStyle("{$colIniEtiqueta}{$filaFiltro}")->applyFromArray([
                                'font' => ['bold' => true],
                                'alignment' => [
                                    'horizontal' => 'left',
                                    'vertical' => 'center',
                                ],
                            ]);

                            // Estilo valor (alineado a la izquierda)
                            $sheet->getStyle("{$colIniValor}{$filaFiltro}")->applyFromArray([
                                'alignment' => [
                                    'horizontal' => 'left',
                                    'vertical' => 'center',
                                ],
                            ]);

                            if ($indiceInfo === 'metros_cama_ha') {
                                $metrosCamaHaRow = $filaFiltro;   // ← guardamos la fila real
                            }
                            // Avanza a la siguiente fila
                            $filaFiltro++;
                        }

                        $baseRow = $filaFiltro + 1; //SUMAMOS 1 PARA UNA FILA EXTRA DE RESPETO
                        $baseCol = $columnaInicioCampania;
                        // 1) TÍTULO PRINCIPAL DEL BLOQUE
                        $sheet->mergeCellsByColumnAndRow($baseCol, $baseRow, $baseCol + 7, $baseRow);
                        $sheet->setCellValueByColumnAndRow($baseCol, $baseRow, "N° DE PLANTAS VIVAS");

                        $sheet->getStyleByColumnAndRow($baseCol, $baseRow)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => 'center']
                        ]);

                        // -----------------------------
// RANGOS QUE NECESITAN FORMATO
// -----------------------------
                        $rangeTitulos = $this->col($baseCol) . ($baseRow + 1) . ':' .
                            $this->col($baseCol + 7) . ($baseRow + 1);

                        $rangeFechas = $this->col($baseCol) . ($baseRow + 2) . ':' .
                            $this->col($baseCol + 7) . ($baseRow + 2);

                        // -----------------------------
// ALINEACIÓN + NEGRITA
// -----------------------------
                        $sheet->getStyle($rangeTitulos)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            ],
                            'font' => [
                                'bold' => true,
                            ],
                        ]);

                        $sheet->getStyle($rangeFechas)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            ],
                            'font' => [
                                'bold' => true,
                            ],
                        ]);

                        // -----------------------------
// FORMATO dd/mm/yyyy SOLO A FECHAS
// -----------------------------
                        $sheet->getStyle(
                            $this->col($baseCol + 4) . ($baseRow + 2) . ':' . $this->col($baseCol + 7) . ($baseRow + 2)
                        )->getNumberFormat()
                            ->setFormatCode('dd/mm/yyyy');

                        // 2) FECHAS CERO Y RESIEMBRA
                        $sheet->mergeCellsByColumnAndRow($baseCol, $baseRow + 1, $baseCol + 3, $baseRow + 2);
                        $sheet->setCellValueByColumnAndRow($baseCol, $baseRow + 1, "FECHA EVALUACIÓN");

                        $sheet->mergeCellsByColumnAndRow($baseCol + 4, $baseRow + 1, $baseCol + 5, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 4, $baseRow + 1, 'EV. CERO');

                        $sheet->mergeCellsByColumnAndRow($baseCol + 6, $baseRow + 1, $baseCol + 7, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 6, $baseRow + 1, 'EV. RESIEMBRA');

                        $sheet->mergeCellsByColumnAndRow($baseCol + 4, $baseRow + 2, $baseCol + 5, $baseRow + 2);
                        $sheet->setCellValueByColumnAndRow($baseCol + 4, $baseRow + 2, $info["fecha_cero"]);

                        $sheet->mergeCellsByColumnAndRow($baseCol + 6, $baseRow + 2, $baseCol + 7, $baseRow + 2);
                        $sheet->setCellValueByColumnAndRow($baseCol + 6, $baseRow + 2, $info["fecha_resiembra"]);

                        // 3) ENCABEZADOS
                        $headers = [
                            "LOTE",
                            "ÁREA LOTE",
                            "N° CAMA MUESTREADA",
                            "LONGITUD CAMA",
                            "PLANTAS POR HILERA",
                            "PLANTAS POR METRO",
                            "PLANTAS POR HILERA",
                            "PLANTAS POR METRO",
                        ];

                        $baseRow += 1;
                        $sheet->getRowDimension($baseRow + 2)->setRowHeight(77.40);

                        foreach ($headers as $i => $h) {
                            $col = $baseCol + $i;
                            $sheet->setCellValueByColumnAndRow($col, $baseRow + 2, $h);
                            $sheet->getStyleByColumnAndRow($col, $baseRow + 2)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                    'wrapText' => true,
                                ],
                            ]);
                        }

                        // 4) DETALLES (8 filas siempre)
                        $startRow = $baseRow + 3;
                        $endRow = $startRow + 7; // 8 filas
    
                        // --- MERGE VERTICAL DE LOTE ---
                        $sheet->mergeCells(
                            $this->col($baseCol) . $startRow . ":" . $this->col($baseCol) . $endRow
                        );
                        $sheet->setCellValueByColumnAndRow($baseCol, $startRow, $campo);

                        // --- MERGE VERTICAL DE ÁREA ---
                        $sheet->mergeCells(
                            $this->col($baseCol + 1) . $startRow . ":" . $this->col($baseCol + 1) . $endRow
                        );
                        $sheet->setCellValueByColumnAndRow($baseCol + 1, $startRow, $info["area_lote"]);

                        // Aplicar centrado a ambos merges
                        $sheet->getStyle(
                            $this->col($baseCol) . $startRow . ":" . $this->col($baseCol + 1) . $endRow
                        )->applyFromArray([
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                    ]
                                ]);

                        // --- DETALLES: 8 filas ---
                        $row = $startRow;
                        for ($i = 0; $i < 8; $i++) {

                            $detalle = $info["detalles"][$i] ?? null;

                            if ($detalle) {

                                $numero = $detalle["numero_cama"];
                                $longitud = $detalle["longitud_cama"];

                                // Nº cama muestreada
                                $sheet->setCellValueByColumnAndRow($baseCol + 2, $row, $numero);

                                // Longitud cama
                                $sheet->setCellValueByColumnAndRow($baseCol + 3, $row, $longitud);

                                // CERO plantas por hilera
                                $sheet->setCellValueByColumnAndRow($baseCol + 4, $row, $detalle["cero"]);

                                // CERO plantas por metro (FÓRMULA)
                                $sheet->setCellValue(
                                    $this->col($baseCol + 5) . $row,
                                    "=+{$this->col($baseCol + 4)}{$row}/{$this->col($baseCol + 3)}{$row}"
                                );

                                // RESIEMBRA plantas por hilera
                                $sheet->setCellValueByColumnAndRow($baseCol + 6, $row, $detalle["resiembra"]);

                                // RESIEMBRA plantas por metro (FÓRMULA)
                                $sheet->setCellValue(
                                    $this->col($baseCol + 7) . $row,
                                    "=+{$this->col($baseCol + 6)}{$row}/{$this->col($baseCol + 3)}{$row}"
                                );

                                // Formato 0 decimales
                                $sheet->getStyle($this->col($baseCol + 5) . $row)->getNumberFormat()->setFormatCode('0');
                                $sheet->getStyle($this->col($baseCol + 7) . $row)->getNumberFormat()->setFormatCode('0');
                            }

                            // ⭐ CENTRADO TOTAL DE TODA LA FILA (columnas baseCol → baseCol+7)
                            $sheet->getStyle(
                                $this->col($baseCol) . $row . ':' . $this->col($baseCol + 7) . $row
                            )->applyFromArray([
                                        'alignment' => [
                                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                        ]
                                    ]);

                            $row++;
                        }

                        // 5) PROMEDIOS
                        $metrosCamaCol = $this->col($baseCol + 3);
                        $promRow = $baseRow + 11;

                        // MERGE primeras 4 columnas
                        $sheet->mergeCells(
                            $this->col($baseCol) . $promRow . ":" . $this->col($baseCol + 3) . $promRow
                        );

                        $sheet->setCellValueByColumnAndRow($baseCol, $promRow, "PROMEDIO");
                        $sheet->getStyleByColumnAndRow($baseCol, $promRow)->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        // CERO promedio hilera
                        $sheet->setCellValue(
                            $this->col($baseCol + 4) . $promRow,
                            "=IFERROR(AVERAGE({$this->col($baseCol + 4)}" . ($baseRow + 3) . ":{$this->col($baseCol + 4)}" . ($baseRow + 10) . "),0)"
                        );

                        // CERO promedio metro
                        $sheet->setCellValue(
                            $this->col($baseCol + 5) . $promRow,
                            "=IFERROR(AVERAGE({$this->col($baseCol + 5)}" . ($baseRow + 3) . ":{$this->col($baseCol + 5)}" . ($baseRow + 10) . "),0)"
                        );

                        // RESIEMBRA promedio hilera
                        $sheet->setCellValue(
                            $this->col($baseCol + 6) . $promRow,
                            "=IFERROR(AVERAGE({$this->col($baseCol + 6)}" . ($baseRow + 3) . ":{$this->col($baseCol + 6)}" . ($baseRow + 10) . "),0)"
                        );

                        // RESIEMBRA promedio metro
                        $sheet->setCellValue(
                            $this->col($baseCol + 7) . $promRow,
                            "=IFERROR(AVERAGE({$this->col($baseCol + 7)}" . ($baseRow + 3) . ":{$this->col($baseCol + 7)}" . ($baseRow + 10) . "),0)"
                        );


                        // --- FORMATO: todos los promedios a 0 decimales y centrados ---
                        foreach ([4, 5, 6, 7] as $colOffset) {
                            $cell = $this->col($baseCol + $colOffset) . $promRow;
                            $sheet->getStyle($cell)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                                ]
                            ]);
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');

                        }

                        // Poner en negrita los promedios por metro
                        $sheet->getStyle($this->col($baseCol + 5) . $promRow)->getFont()->setBold(true);
                        $sheet->getStyle($this->col($baseCol + 7) . $promRow)->getFont()->setBold(true);


                        // 6) PROMEDIO POR HECTÁREA
                        $haRow = $baseRow + 12;

                        // MERGE primeras 4 columnas
                        $sheet->mergeCells(
                            $this->col($baseCol) . $haRow . ":" . $this->col($baseCol + 3) . $haRow
                        );

                        $sheet->setCellValueByColumnAndRow($baseCol, $haRow, "PROMEDIO PLANTAS HA");
                        $sheet->getStyleByColumnAndRow($baseCol, $haRow)->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        // cero
                        $sheet->setCellValue(
                            $this->col($baseCol + 4) . $haRow,
                            "=+{$this->col($baseCol + 5)}{$promRow} * {$metrosCamaCol}{$metrosCamaHaRow}"
                        );

                        // resiembra
                        $sheet->setCellValue(
                            $this->col($baseCol + 6) . $haRow,
                            "=+{$this->col($baseCol + 7)}{$promRow} * {$metrosCamaCol}{$metrosCamaHaRow}"
                        );

                        // formato, centrado, 0 decimales
                        foreach ([4, 6] as $colOffset) {
                            $cell = $this->col($baseCol + $colOffset) . $haRow;
                            $sheet->getStyle($cell)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                                ]
                            ]);
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                            $sheet->getStyle($cell)->getFont()->setBold(true);
                        }


                        $sheet->getStyle(
                            $this->col($baseCol + 5) . ($baseRow + 3) . ":" .
                            $this->col($baseCol + 5) . ($baseRow + 10)
                        )->applyFromArray([
                                    'fill' => [
                                        'fillType' => 'solid',
                                        'color' => ['rgb' => 'DBDBDB']
                                    ]
                                ]);
                        $sheet->getStyle(
                            $this->col($baseCol + 7) . ($baseRow + 3) . ":" .
                            $this->col($baseCol + 7) . ($baseRow + 10)
                        )->applyFromArray([
                                    'fill' => [
                                        'fillType' => 'solid',
                                        'color' => ['rgb' => 'DBDBDB']
                                    ]
                                ]);

                        for ($i = 0; $i < 8; $i++) {
                            $sheet->getStyle(
                                $this->col($baseCol + $i) . ($baseRow + 2)
                            )->getAlignment()->setTextRotation(90);
                        }

                        $sheet->getStyle(
                            $this->col($baseCol) . ($baseRow - 1) . ":" .
                            $this->col($baseCol + 7) . ($baseRow + 12)
                        )->applyFromArray([
                                    'borders' => [
                                        'allBorders' => [
                                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                                        ]
                                    ]
                                ]);

                        // CERO
                        $sheet->mergeCells(
                            $this->col($baseCol + 4) . $haRow . ":" .
                            $this->col($baseCol + 5) . $haRow
                        );

                        // RESIEMBRA
                        $sheet->mergeCells(
                            $this->col($baseCol + 6) . $haRow . ":" .
                            $this->col($baseCol + 7) . $haRow
                        );
                        $sheet->getStyle(
                            $this->col($baseCol + 4) . $haRow . ":" .
                            $this->col($baseCol + 7) . $haRow
                        )->applyFromArray([
                                    'alignment' => ['horizontal' => 'center']
                                ]);

                        $sheet->getStyle($this->col($baseCol + 4) . $promRow . ":" .
                            $this->col($baseCol + 7) . $promRow)
                            ->applyFromArray(['font' => ['bold' => true]]);
                        $columnaInicioCampania += $anchoBloqueCampania;
                    }

                    $filaInicioCampo += $altoBloqueCampo;

                }
            }
        ];
    }
    private function col($num)
    {
        $string = "";
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $string = chr(65 + $mod) . $string;
            $num = intval(($num - $mod) / 26);
        }
        return $string;
    }

}
