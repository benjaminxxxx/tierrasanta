<?php

namespace App\Exports\Cuadrilla;

use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CuadrillaRptPagoConsolidadoExport implements FromArray, WithEvents, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return "Consolidado";
    }

    public function array(): array
    {
        $rows = [];

        // Fila 1: Fecha (alineada con hoja Pagos)
        $rows[] = [formatear_fecha($this->data['fecha_reporte'])];

        // Fila 2: Título consolidado
        $rows[] = [$this->data['titulo_consolidado']];

        // Fila 3 y 4: Espacio
        $rows[] = [''];

        // Fila 5: Header principal (para alinear con la fila 4 de Pagos)
        $header = ['N°', 'NOMBRES'];

        // Agregar columnas de semanas dinámicamente
        foreach ($this->data['tramos'] as $index => $tramo) {
            $header[] = 'SEMANA ' . ($index + 1);
        }

        $header[] = 'TOTAL SEMANAL';
        $header[] = 'TOTAL BONO';
        $header[] = 'TOTAL';

        $rows[] = $header;
        $rows[] = ['']; // en la hoja Pagos hay dos filas, aquí reservamos espacio

        // 🚀 Tomamos la lista de trabajadores del primer tramo
        $trabajadores = $this->data['tramos'][0]['pagos'];

        $numeroTrabajador = 0;
        foreach ($trabajadores as $pagoTrabajador) {
            $nombreTrabajador = $pagoTrabajador['nombre'];
            $row = [$numeroTrabajador + 1, $nombreTrabajador];

            // Recorremos cada tramo
            foreach ($this->data['tramos'] as $indice => $tramo) {
                $filaInicial = $this->calcularFilaInicialTramo($indice);

                // fila del trabajador dentro del tramo
                $filaTotal = $filaInicial + $numeroTrabajador;

                // calcular columna MONTO dinámicamente (depende de cuántos días hay)
                $numDias = count(
                    CarbonPeriod::create(
                        Carbon::parse($tramo['fecha_inicio']),
                        Carbon::parse($tramo['fecha_fin'])
                    )
                );
                $colIndex = 2 + $numDias + 1; // A=1, B=2, C=3...
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                // referencia a la hoja Pagos
                $celdaReferencia = "Pagos!{$colLetter}{$filaTotal}";
                $row[] = "={$celdaReferencia}";
            }

            // Buscar bono para este trabajador
            $bono = 0;
            foreach ($this->data['bonos'] as $bonoData) {
                if ($bonoData['nombre'] === $nombreTrabajador) {
                    $bono = $bonoData['bono'];
                    break;
                }
            }
            $trabajadores = $numeroTrabajador + 6;

            
            $row[] = "=SUM(C{$trabajadores}:" . Coordinate::stringFromColumnIndex(count($this->data['tramos']) + 2) . ($numeroTrabajador + 6) . ")";
            $row[] = $bono;
            $row[] =  "=SUM(" . Coordinate::stringFromColumnIndex(1 + count($this->data['tramos']) + 2) . ($numeroTrabajador + 6) . ":".Coordinate::stringFromColumnIndex(2 + count($this->data['tramos']) + 2) . ($numeroTrabajador + 6).")";

            $rows[] = $row;



            //formula para suma totales por tramo + total de bonos + total de totales
            $numeroTrabajador++;
        }

        // 🚀 después del foreach de trabajadores, agregamos fila de TOTALES
        $filaInicioTrabajadores = 6; // primera fila real con data (después de headers)
        $filaFinTrabajadores = $numeroTrabajador + 5; // última fila ocupada por un trabajador

        $filaTotales = ['', 'TOTAL GENERAL'];

        // columnas de tramos
        for ($i = 0; $i < count($this->data['tramos']); $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(3 + $i); // C=3
            $filaTotales[] = "=SUM({$colLetter}{$filaInicioTrabajadores}:{$colLetter}{$filaFinTrabajadores})";
        }

        // columna BONO
        $colBono = Coordinate::stringFromColumnIndex(3 + count($this->data['tramos']));
        $filaTotales[] = "=SUM({$colBono}{$filaInicioTrabajadores}:{$colBono}{$filaFinTrabajadores})";

        // columna TOTAL (suma de totales individuales)
        $colTotalBonos = Coordinate::stringFromColumnIndex(4 + count($this->data['tramos']));
        $colTotal = Coordinate::stringFromColumnIndex(5 + count($this->data['tramos']));

        $filaTotales[] = "=SUM({$colTotalBonos}{$filaInicioTrabajadores}:{$colTotalBonos}{$filaFinTrabajadores})";
        $filaTotales[] = "=SUM({$colTotal}{$filaInicioTrabajadores}:{$colTotal}{$filaFinTrabajadores})";

        $rows[] = $filaTotales;

        /**************************************************************************
         * BONOS 
         */
        // Debe alinear con donde termina la tabla principal de la hoja Pagos
        $rows[] = [''];
        $rows[] = [formatear_fecha($this->data['fecha_reporte'])];

        // Tabla de resumen de bonos (empezando en fila 24 como en la imagen)
        $rows[] = [$this->data['titulo_bono']];
        $rows[] = [];
        $rows[] = ['N°', 'NOMBRES', 'MONTO', 'FIRMA'];

        $numeroBono = 1;
        $filaInicioBonos = count($rows);

        foreach ($this->data['bonos'] as $bonoData) {
            $rows[] = [
                $numeroBono,
                $bonoData['nombre'],
                $bonoData['bono'],
                ''
            ];
            $numeroBono++;
        }

        // IMPORTANT: calcular fila fin ANTES de agregar la fila del total
        $filaFinBonos = count($rows) - 1; // última fila que contiene un bono

        // Si hay al menos una fila de bonos, agregamos la fórmula que suma desde inicio..fin
        if ($filaFinBonos >= $filaInicioBonos) {
            $rows[] = ['', 'TOTAL BONOS', "=SUM(C{$filaInicioBonos}:C{$filaFinBonos})", ''];
        } else {
            // no hay bonos -> ponemos 0 o dejamos vacío para evitar referencias inválidas
            $rows[] = ['', 'TOTAL BONOS', 0, ''];
        }
        return $rows;
    }

    protected function calcularFilaInicialTramo($index)
    {
        $filaBase = 6; // la fila donde empieza el primer tramo en Pagos
        $offset = 0;

        for ($i = 0; $i < $index; $i++) {
            $offset += count($this->data['tramos'][$i]['pagos']) + 6;
            // 6 = filas de encabezado + total + espacios
        }

        return $filaBase + $offset;
    }
    private function obtenerTrabajadoresUnicos(): array
    {
        $trabajadores = [];

        // Obtener trabajadores de todos los tramos
        foreach ($this->data['tramos'] as $tramo) {
            foreach ($tramo['pagos'] as $pago) {
                if (!in_array($pago['nombre'], $trabajadores)) {
                    $trabajadores[] = $pago['nombre'];
                }
            }
        }

        return $trabajadores;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $trabajadores = $this->data['tramos'][0]['pagos'];

                $totalTrabajadores = count($trabajadores);
                $totalColumnas = 2 + count($this->data['tramos']) + 3; // N°, NOMBRES, SEMANAS, BONO, TOTAL, FIRMA
                $lastCol = $sheet->getCellByColumnAndRow($totalColumnas, 1)->getColumn();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}3");
                $sheet->mergeCells("A4:A5");
                $sheet->mergeCells("B4:B5");
                $columnaInicioTramo = 'C';
                foreach ($this->data['tramos'] as $tramo) {
                    $sheet->mergeCells("{$columnaInicioTramo}4:{$columnaInicioTramo}5");
                    $columnaInicioTramo++;
                }
                $sheet->mergeCells("{$columnaInicioTramo}4:{$columnaInicioTramo}5");
                $columnaInicioTramo++;
                $sheet->mergeCells("{$columnaInicioTramo}4:{$columnaInicioTramo}5");
                $columnaInicioTramo++;
                $sheet->mergeCells("{$columnaInicioTramo}4:{$columnaInicioTramo}5");
                $columnaInicioTramo++;

                $sheet->getStyle('3:3')->getAlignment()->setWrapText(true);
                $sheet->getStyle('4:4')->getAlignment()->setWrapText(true);

                $sheet->getStyle("A1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                // Estilos para título bono
                $sheet->getStyle("A2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Estilos para header principal (fila 4)
                $sheet->getStyle("A4:{$lastCol}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE5E5E5'],
                    ],
                ]);

                // Estilos para data de trabajadores
                $filaInicioData = 6;
                $filaFinData = 6 + $totalTrabajadores;

                $sheet->getStyle("A{$filaInicioData}:{$lastCol}{$filaFinData}")->applyFromArray([
                    'font' => ['size' => 9],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Centrar números de trabajador
                $sheet->getStyle("A{$filaInicioData}:A{$filaFinData}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Formato de moneda para columnas de montos
                $colInicioMontos = 3; // Primera columna de semana
                $colFinMontos = $totalColumnas; // Hasta TOTAL A PAGAR - 1 de firma
                $colLetraInicio = $sheet->getCellByColumnAndRow($colInicioMontos, 1)->getColumn();
                $colLetraFin = $sheet->getCellByColumnAndRow($colFinMontos, 1)->getColumn();

                $sheet->getStyle("{$colLetraInicio}{$filaInicioData}:{$colLetraFin}{$filaFinData}")
                    ->getNumberFormat()
                    ->setFormatCode('"S/ "#,##0.00');

                $sheet->setBreak("A{$filaFinData}", Worksheet::BREAK_ROW);
                /***********************************************************************************************
                 * BONOS
                 */

                $filaHeaderBonos = $filaFinData + 2;

                // Merge fecha y título resumen bonos
                $sheet->mergeCells("A{$filaHeaderBonos}:E{$filaHeaderBonos}");
                $sheet->mergeCells("A" . ($filaHeaderBonos + 1) . ":E" . ($filaHeaderBonos + 1));
                $sheet->mergeCells("D" . ($filaHeaderBonos + 2) . ":E" . ($filaHeaderBonos + 2));
                $sheet->mergeCells("D" . ($filaHeaderBonos + 2) . ":E" . ($filaHeaderBonos + 2));

                // Estilos para fecha y título resumen bonos
                $sheet->getStyle("A{$filaHeaderBonos}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                $sheet->getStyle("A" . ($filaHeaderBonos + 1))->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getRowDimension($filaHeaderBonos + 1)->setRowHeight(40);

                $filaHeaderBonos += 2;
                // Estilos para header resumen bonos
    
                $sheet->getStyle("A{$filaHeaderBonos}:E{$filaHeaderBonos}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE5E5E5'],
                    ],
                ]);
                // Estilos para data resumen bonos
                $filaInicioBonos = $filaHeaderBonos + 1;
                $filaFinBonos = $filaInicioBonos + count($this->data['bonos']);

                $sheet->mergeCells("D" . ($filaFinBonos) . ":E" . ($filaFinBonos));

                if (count($this->data['bonos']) > 0) {
                    $sheet->getStyle("A{$filaInicioBonos}:E{$filaFinBonos}")->applyFromArray([
                        'font' => ['size' => 9],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                    // Centrar números en resumen bonos
                    $sheet->getStyle("A{$filaInicioBonos}:A{$filaFinBonos}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);

                    // Formato moneda para montos bonos
                    $sheet->getStyle("C{$filaInicioBonos}:C{$filaFinBonos}")
                        ->getNumberFormat()
                        ->setFormatCode('"S/ "#,##0.00');
                }

                $altura = 40 * (100 / 166);
                for ($i = $filaInicioBonos; $i < $filaFinBonos; $i++) {
                 
                    $sheet->getRowDimension($i)->setRowHeight($altura);
                    
                    $sheet->mergeCells("D{$i}:E{$i}");
                }

                

                // Configuración de página
                /*
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
*/
                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getPageSetup()->setHorizontalCentered(true);

                // Ajustar anchos de columnas
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setAutoSize(true);

                // Columnas de semanas y totales
                for ($i = 3; $i <= $totalColumnas; $i++) {
                    $colLetter = $sheet->getCellByColumnAndRow($i, 1)->getColumn();
                    if ($i <= 2 + count($this->data['tramos'])) {
                        $sheet->getColumnDimension($colLetter)->setWidth(12); // Columnas de semanas
                    } else {
                        $sheet->getColumnDimension($colLetter)->setWidth(10); // Otras columnas
                    }
                }
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}