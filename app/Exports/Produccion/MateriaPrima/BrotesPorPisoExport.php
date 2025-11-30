<?php

namespace App\Exports\Produccion\MateriaPrima;

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
                $anchoBloqueCampania = 14;



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

                        $sheet->mergeCellsByColumnAndRow($baseCol + 3, $baseRow + 1, $baseCol + 4, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 3, $baseRow + 1, "N° ACTUAL DE BROTES APTOS  2° PISO POR HECTAREA");

                        $sheet->mergeCellsByColumnAndRow($baseCol + 5, $baseRow + 1, $baseCol + 6, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 5, $baseRow + 1, "N° DE BROTES APTOS 2° PISO DESPUES DE 30 DIAS");

                        $sheet->mergeCellsByColumnAndRow($baseCol + 7, $baseRow + 1, $baseCol + 8, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 7, $baseRow + 1, "N° ACTUAL DE BROTES APTOS  3° PISO");

                        $sheet->mergeCellsByColumnAndRow($baseCol + 9, $baseRow + 1, $baseCol + 10, $baseRow + 1);
                        $sheet->setCellValueByColumnAndRow($baseCol + 9, $baseRow + 1, "N° DE BROTES APTOS 3° PISO DESPUES DE 30 DIAS");

                        $sheet->setCellValueByColumnAndRow($baseCol + 11, $baseRow + 1, 'TOTAL ACTUAL DE BROTES APTOS 2° Y 3° PISO');
                        $sheet->setCellValueByColumnAndRow($baseCol + 12, $baseRow + 1, 'TOTAL DE BROTES APTOS 2° Y 3° PISO DESPUES DE 30 DIAS');

                        // ------------------------------------------------------------------
                        // ESTILOS DEL ENCABEZADO DE COLUMNAS
                        // ------------------------------------------------------------------
    
                        // Rango completo del encabezado (solo la fila baseRow+1)
                        $headerRange = $this->col($baseCol) . ($baseRow + 1) . ':' .
                            $this->col($baseCol + $anchoBloqueCampania - 2) . ($baseRow + 1);

                        // Toda la fila → negrita + bordes thin
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

                        // ------------------------------------------------------------------
                        // ORIENTAR TEXTO HACIA ARRIBA
                        // (3 primeras columnas + 2 últimas)
                        // ------------------------------------------------------------------
    
                        $rotateCols = [];

                        // 3 primeras columnas
                        for ($i = 0; $i < 3; $i++) {
                            $rotateCols[] = $this->col($baseCol + $i);
                        }

                        // 2 últimas columnas
                        $rotateCols[] = $this->col($baseCol + $anchoBloqueCampania - 3);
                        $rotateCols[] = $this->col($baseCol + $anchoBloqueCampania - 2);

                        // Aplicar rotación ↑ a cada columna
                        foreach ($rotateCols as $colLetter) {
                            $sheet->getStyle($colLetter . ($baseRow + 1))
                                ->getAlignment()
                                ->setTextRotation(90); // Girar texto hacia arriba
                        }

                        // 4) DETALLES (8 filas siempre)
                        $startRow = $baseRow + 2;
                        $endRow = $startRow + 11; // 12 filas
    
                        // --- MERGE VERTICAL DE LOTE ---
                        $sheet->mergeCells(
                            $this->col($baseCol) . $startRow . ":" . $this->col($baseCol) . $endRow
                        );
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
                        for ($i = 0; $i < 12; $i++) {

                            $detalle = $info["detalles"][$i] ?? null;

                            if ($detalle) {
                                /*
                                array:6 [▼ // app\Exports\Produccion\MateriaPrima\BrotesPorPisoExport.php:279
                                 "numero_cama" => 1
                                 "longitud_cama" => "124.00"
                                 "brotes_2p_actual" => 12
                                 "brotes_2p_despues_n_dias" => 14
                                 "brotes_3p_actual" => 0
                                 "brotes_3p_despues_n_dias" => 0
                                 ]
                                 */

                                $sheet->setCellValueByColumnAndRow($baseCol, $row, $campo);
                                $sheet->setCellValueByColumnAndRow($baseCol + 1, $row, $detalle["numero_cama"]);
                                $sheet->setCellValueByColumnAndRow($baseCol + 2, $row, $detalle["longitud_cama"]);

                                $sheet->setCellValueByColumnAndRow($baseCol + 3, $row, $detalle["brotes_2p_actual"]);
                                $sheet->setCellValueByColumnAndRow($baseCol + 5, $row, $detalle["brotes_2p_despues_n_dias"]);
                                $sheet->setCellValueByColumnAndRow($baseCol + 7, $row, $detalle["brotes_3p_actual"]);
                                $sheet->setCellValueByColumnAndRow($baseCol + 9, $row, $detalle["brotes_3p_despues_n_dias"]);
                                // ---------------------------------------------------------------
// FORMULAS POR FILA
// ---------------------------------------------------------------
    
                                // Para evitar escribir muchas veces la función col()
                                $col = fn($i) => $this->col($baseCol + $i);

                                // Columnas base
                                $colC = $col(2);  // longitud de cama
                                $colD = $col(3);
                                $colF = $col(5);
                                $colH = $col(7);
                                $colJ = $col(9);

                                // Valor metros por cama ha (celda absoluta tipo $D$10)
                                $metrosPorCamaCelda = $this->col($baseCol + 3) . $metrosCamaHaRow;

                                // 1) =SI.ERROR((D/C) * $D$10 ; 0)
                                $sheet->setCellValue(
                                    $col(4) . $row,   // E
                                    "=IFERROR(($colD$row/$colC$row)*\${$metrosPorCamaCelda},0)"
                                );

                                // 2) =SI.ERROR((F/C) * $D$10 ; 0)
                                $sheet->setCellValue(
                                    $col(6) . $row,   // G
                                    "=IFERROR(($colF$row/$colC$row)*\${$metrosPorCamaCelda},0)"
                                );

                                // 3) =SI.ERROR((H/C) * $D$10 ; 0)
                                $sheet->setCellValue(
                                    $col(8) . $row,   // I
                                    "=IFERROR(($colH$row/$colC$row)*\${$metrosPorCamaCelda},0)"
                                );

                                // 4) =SI.ERROR((J/C) * $D$10 ; 0)
                                $sheet->setCellValue(
                                    $col(10) . $row,   // K
                                    "=IFERROR(($colJ$row/$colC$row)*\${$metrosPorCamaCelda},0)"
                                );

                                // 5) =E + I
                                $sheet->setCellValue(
                                    $col(11) . $row,   // L
                                    "= {$col(4)}{$row} + {$col(8)}{$row}"
                                );

                                // 6) =G + K
                                $sheet->setCellValue(
                                    $col(12) . $row,   // M
                                    "= {$col(6)}{$row} + {$col(10)}{$row}"
                                );


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
                        // ---------------------------------------------------------------
                        // PROMEDIOS POR COLUMNA (E, G, I, K, L, M)
                        // ---------------------------------------------------------------
    
                        $filaProm = $startRow + 12;      // Fila donde irán los promedios
                        $firstRow = $startRow;
                        $lastRow = $startRow + 11;      // 11 filas exactas
    
                        $col = fn($i) => $this->col($baseCol + $i);

                        // Fórmulas PROMEDIO.SI (>0)
                        $sheet->setCellValue(
                            $col(4) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(4)}{$firstRow}:{$col(4)}{$lastRow},\">0\"),0)"
                        );

                        $sheet->setCellValue(
                            $col(6) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(6)}{$firstRow}:{$col(6)}{$lastRow},\">0\"),0)"
                        );

                        $sheet->setCellValue(
                            $col(8) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(8)}{$firstRow}:{$col(8)}{$lastRow},\">0\"),0)"
                        );

                        $sheet->setCellValue(
                            $col(10) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(10)}{$firstRow}:{$col(10)}{$lastRow},\">0\"),0)"
                        );

                        $sheet->setCellValue(
                            $col(11) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(11)}{$firstRow}:{$col(11)}{$lastRow},\">0\"),0)"
                        );

                        $sheet->setCellValue(
                            $col(12) . $filaProm,
                            "=IFERROR(AVERAGEIF({$col(12)}{$firstRow}:{$col(12)}{$lastRow},\">0\"),0)"
                        );

                        // ---------------------------------------------------------------
                        // FORMATO – COLORES
                        // ---------------------------------------------------------------
                        // ==================================================================
                        // ESTILOS FINALES (Bordes, Colores, Formatos)
                        // ==================================================================
    
                        // 1. BORDE AL TÍTULO PRINCIPAL ("CONTEO DE BROTES...")
                        $sheet->getStyleByColumnAndRow($baseCol, $baseRow, $baseCol + $anchoBloqueCampania - 2, $baseRow)
                            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                        // 2. BORDE A TODO EL CONTENIDO (Encabezados + Datos + Promedios)
                        // Desde la fila de encabezados ($baseRow + 1) hasta la fila de promedios ($filaProm)
                        // Desde columna 0 hasta columna 12
                        $rangoCompleto = $col(0) . ($baseRow + 1) . ':' . $col(12) . $filaProm;
                        $sheet->getStyle($rangoCompleto)
                            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                        // 2) Promedios normales (E,G,I,K) — Gris suave
                        $sheet->getStyle($col(4) . $filaProm . ':' . $col(10) . $filaProm)
                            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('EDEDED');

                        // 3. COLOR AMARILLO CLARO EN COLUMNAS DE FÓRMULAS (5, 7, 9, 11 -> Índices 4, 6, 8, 10)
                        $columnasAmarillas = [4, 6, 8, 10];
                        foreach ($columnasAmarillas as $idx) {
                            $rangoAmarillo = $col($idx) . $startRow . ':' . $col($idx) . $filaProm;
                            $sheet->getStyle($rangoAmarillo)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFFFE0'], // Amarillo Claro
                                ]
                            ]);
                        }

                        // 3) Promedios columnas finales (L, M) — Verde
                        $sheet->getStyle($col(11) . $firstRow . ':' . $col(12) . $filaProm)
                            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('A9D08E');

                        // Formato 0 decimales
                        $sheet->getStyle($col(11) . $firstRow . ':' . $col(12) . $filaProm)->getNumberFormat()->setFormatCode('0');

                        // 5. NEGRITA EN LA FILA DE PROMEDIOS
                        $sheet->getStyle($col(0) . $filaProm . ':' . $col(12) . $filaProm)
                            ->getFont()->setBold(true);

                        // 6. FORMATO NUMÉRICO (#,##0) Y CENTRADO
                        // Aplicamos esto a todas las celdas numéricas (desde Col 1 hasta Col 12, filas startRow a filaProm)
                        // Excluimos Col 0 (Campo) que es texto.
                        $rangoNumerico = $col(1) . $startRow . ':' . $col(12) . $filaProm;

                        $sheet->getStyle($rangoNumerico)->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            ],
                            'numberFormat' => [
                                // Formato entero con separador de miles, redondea visualmente a 0 decimales
                                'formatCode' => '#,##0'
                            ]
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
