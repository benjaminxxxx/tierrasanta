<?php

namespace App\Exports\Evaluacion;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class BrotesPorPisoExport implements FromArray, WithEvents, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data; // contiene filtros y datos
    }

    public function title(): string
    {
        return 'BROTES POR PISO';
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
                $sheet->setCellValue('A1', 'EVALUACION BROTES POR PISO');

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
                $altoBloqueCampo = 26;
                $anchoBloqueCampania = 18; // 17 columnas (con 4° piso) + 1 de separación



                //-----------------------------------------
                // RECORRER CAMPOS (vertical)
                //-----------------------------------------
    
                foreach ($datos as $campo => $campanias) {

                    // Flag horizontal se reinicia para cada campo
                    $columnaInicioCampania = 1; // A
    
                    foreach ($campanias as $campania => $info) {

                        $mapPorCartilla = [
                            'fecha' => 'FECHA DE EVALUACIÓN',
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
                            $colFinValor = $this->col($columnaInicioCampania + $anchoBloqueCampania - 3);

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

                        // ------------------------------------------------------------------
                        // ANCHO GENERAL DE COLUMNAS + WRAP TEXT
                        // ------------------------------------------------------------------
                        for ($c = 0; $c < $anchoBloqueCampania; $c++) {
                            $colLetter = $this->col($baseCol + $c);
                            $sheet->getColumnDimension($colLetter)->setWidth(10); // 95 píxeles aprox
                            $sheet->getStyle("{$colLetter}")->getAlignment()->setWrapText(true);
                        }

                        // 1) TÍTULO PRINCIPAL DEL BLOQUE
                        $sheet->mergeCellsByColumnAndRow($baseCol, $baseRow, $baseCol + $anchoBloqueCampania - 2, $baseRow);
                        $sheet->setCellValueByColumnAndRow($baseCol, $baseRow, "CONTEO DE BROTES PARA INFESTACIÓN");

                        $sheet->getStyleByColumnAndRow($baseCol, $baseRow)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => 'center']
                        ]);

                        $sheet->setCellValueByColumnAndRow($baseCol, $baseRow + 1, "CAMPO");
                        $sheet->setCellValueByColumnAndRow($baseCol + 1, $baseRow + 1, 'N° DE CAMA MUESTREADA');
                        $sheet->setCellValueByColumnAndRow($baseCol + 2, $baseRow + 1, 'LONGITUD CAMA (metros)');

                        // Pares (cama, por ha) por piso; el 4° piso es opcional (evaluaciones desde oct-2026)
                        $pares = [
                            3 => 'N° ACTUAL DE BROTES APTOS 2° PISO POR HECTÁREA',
                            5 => 'N° DE BROTES DEL 2° PISO PARA ELIMINAR',
                            7 => 'N° ACTUAL DE BROTES APTOS 3° PISO',
                            9 => 'N° DE BROTES DEL 3° PISO PARA ELIMINAR',
                            11 => 'N° ACTUAL DE BROTES APTOS 4° PISO',
                            13 => 'N° DE BROTES DEL 4° PISO PARA ELIMINAR',
                        ];
                        foreach ($pares as $desde => $titulo) {
                            $sheet->mergeCellsByColumnAndRow($baseCol + $desde, $baseRow + 1, $baseCol + $desde + 1, $baseRow + 1);
                            $sheet->setCellValueByColumnAndRow($baseCol + $desde, $baseRow + 1, $titulo);
                        }
                        $sheet->setCellValueByColumnAndRow($baseCol + 15, $baseRow + 1, 'TOTAL ACTUAL DE BROTES APTOS 2°, 3° Y 4° PISO');
                        $sheet->setCellValueByColumnAndRow($baseCol + 16, $baseRow + 1, 'TOTAL DE BROTES PARA ELIMINAR (informativo)');

                        // ------------------------------------------------------------------
                        // ESTILOS DEL ENCABEZADO DE COLUMNAS
                        // ------------------------------------------------------------------
                        $headerRange = $this->col($baseCol) . ($baseRow + 1) . ':' .
                            $this->col($baseCol + $anchoBloqueCampania - 2) . ($baseRow + 1);

                        $sheet->getStyle($headerRange)->applyFromArray([
                            'font' => ['bold' => true],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ]
                            ],
                            'alignment' => [
                                'horizontal' => 'center',
                                'vertical' => 'center',
                            ]
                        ]);

                        // Texto hacia arriba: 3 primeras columnas + 2 de totales
                        $rotateCols = [];
                        for ($i = 0; $i < 3; $i++) {
                            $rotateCols[] = $this->col($baseCol + $i);
                        }
                        $rotateCols[] = $this->col($baseCol + 15);
                        $rotateCols[] = $this->col($baseCol + 16);
                        foreach ($rotateCols as $colLetter) {
                            $sheet->getStyle($colLetter . ($baseRow + 1))->getAlignment()->setTextRotation(90);
                        }

                        // 4) DETALLES (12 filas siempre)
                        $startRow = $baseRow + 2;
                        $endRow = $startRow + 11;

                        $sheet->mergeCells($this->col($baseCol) . $startRow . ":" . $this->col($baseCol) . $endRow);
                        $sheet->getStyle($this->col($baseCol) . $startRow . ":" . $this->col($baseCol + 1) . $endRow)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            ]
                        ]);

                        $col = fn($i) => $this->col($baseCol + $i);
                        // Valor metros de cama por ha (celda absoluta)
                        $metrosPorCamaCelda = $this->col($baseCol + 3) . $metrosCamaHaRow;
                        // Columna de entrada => dato del detalle
                        $entradas = [3 => 'brotes_2p_actual', 5 => 'brotes_2p_despues_n_dias', 7 => 'brotes_3p_actual',
                            9 => 'brotes_3p_despues_n_dias', 11 => 'brotes_4p_actual', 13 => 'brotes_4p_despues_n_dias'];

                        $row = $startRow;
                        for ($i = 0; $i < 12; $i++) {
                            $detalle = $info["detalles"][$i] ?? null;

                            if ($detalle) {
                                $sheet->setCellValueByColumnAndRow($baseCol, $row, $campo);
                                $sheet->setCellValueByColumnAndRow($baseCol + 1, $row, $detalle["numero_cama"]);
                                $sheet->setCellValueByColumnAndRow($baseCol + 2, $row, $detalle["longitud_cama"]);

                                foreach ($entradas as $c => $clave) {
                                    $valor = $detalle[$clave] ?? null;
                                    if ($valor !== null) {
                                        $sheet->setCellValueByColumnAndRow($baseCol + $c, $row, $valor);
                                    }
                                    // Por ha = (brotes / longitud) * metros de cama por ha
                                    $sheet->setCellValue($col($c + 1) . $row,
                                        "=IFERROR(({$col($c)}{$row}/{$col(2)}{$row})*\${$metrosPorCamaCelda},0)");
                                }

                                // Total aptos = 2° + 3° + 4° por ha; total para eliminar (informativo)
                                $sheet->setCellValue($col(15) . $row, "={$col(4)}{$row}+{$col(8)}{$row}+{$col(12)}{$row}");
                                $sheet->setCellValue($col(16) . $row, "={$col(6)}{$row}+{$col(10)}{$row}+{$col(14)}{$row}");
                            }

                            $row++;
                        }

                        // ---------------------------------------------------------------
                        // PROMEDIOS POR COLUMNA (por ha y total aptos; el total para eliminar no se promedia)
                        // ---------------------------------------------------------------
                        $filaProm = $startRow + 12;
                        $firstRow = $startRow;
                        $lastRow = $startRow + 11;

                        foreach ([4, 6, 8, 10, 12, 14, 15] as $c) {
                            $sheet->setCellValue($col($c) . $filaProm,
                                "=IFERROR(AVERAGEIF({$col($c)}{$firstRow}:{$col($c)}{$lastRow},\">0\"),0)");
                        }

                        // ==================================================================
                        // ESTILOS FINALES (Bordes, Colores, Formatos)
                        // ==================================================================
                        $ultima = 16;
                        $sheet->getStyleByColumnAndRow($baseCol, $baseRow, $baseCol + $anchoBloqueCampania - 2, $baseRow)
                            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                        $sheet->getStyle($col(0) . ($baseRow + 1) . ':' . $col($ultima) . $filaProm)
                            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                        // Promedios por piso — gris suave
                        $sheet->getStyle($col(4) . $filaProm . ':' . $col(14) . $filaProm)
                            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('EDEDED');

                        // Columnas por ha — amarillo claro
                        foreach ([4, 6, 8, 10, 12, 14] as $idx) {
                            $sheet->getStyle($col($idx) . $startRow . ':' . $col($idx) . $filaProm)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFFFE0'],
                                ]
                            ]);
                        }

                        // Total aptos (el que sirve) — naranja como el formato del ingeniero; para eliminar — gris
                        $sheet->getStyle($col(15) . $firstRow . ':' . $col(15) . $filaProm)
                            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFC000');
                        $sheet->getStyle($col(16) . $firstRow . ':' . $col(16) . $filaProm)
                            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('EDEDED');

                        // Negrita en la fila de promedios
                        $sheet->getStyle($col(0) . $filaProm . ':' . $col($ultima) . $filaProm)->getFont()->setBold(true);

                        // Formato numérico y centrado
                        $sheet->getStyle($col(1) . $startRow . ':' . $col($ultima) . $filaProm)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            ],
                            'numberFormat' => ['formatCode' => '#,##0'],
                        ]);

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
