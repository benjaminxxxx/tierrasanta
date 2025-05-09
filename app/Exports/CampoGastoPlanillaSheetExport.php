<?php

namespace App\Exports;

use App\Models\ReporteCostoPlanilla;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CampoGastoPlanillaSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    public $data;
    private $index;
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function title(): string
    {
        return 'GASTO PLANILLA';
    }

    public function collection()
    {
        return ReporteCostoPlanilla::where('campos_campanias_id', $this->data['campos_campanias_id'])
            ->with('campania')
            ->orderBy('fecha')
            ->get();
    }

    public function headings(): array
    {
        return [
            'N° Orden',
            'Campaña',
            'Documento',
            'Empleado',
            'Fecha',
            'Campo',
            'Horas total x dia',
            'Hora inicial',
            'Hora final',
            'Total de horas',
            'Costo hora',
            'Gasto',
            'Gasto bono',
            'Gasto total',
        ];
    }

    public function map($reporte): array
    {
        $this->index++; // Incrementar índice
        $fila = $this->index + 1; // La fila de Excel donde se encuentra la fórmula
    
        return [
            $this->index,
            $reporte->campania->nombre_campania,            
            $reporte->documento,
            $reporte->empleado_nombre,
            $reporte->fecha,
            $reporte->campo,
            $reporte->horas_totales,
            $reporte->hora_inicio,
            $reporte->hora_salida,
            $reporte->hora_diferencia_entero,
            $reporte->costo_hora,
            $reporte->gasto,
            $reporte->gasto_bono,
            "=K{$fila}+L{$fila}",
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '056A70',
                ],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A1:J' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);

        // Ajustar las dimensiones de las filas y columnas
        $sheet->getRowDimension(1)->setRowHeight(27);
        
        $sheet->getStyle('A1:E'.$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I1:J'.$sheet->getHighestRow()+1)->getNumberFormat()->setFormatCode('"S/" #,##0.00;[Red]"S/" -#,##0.00;"S/" "-"');

        return [];
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event){
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->index+1;
                $sheet->setCellValue('G' . ($lastRow+1),'TOTAL');
                $sheet->setCellValue('H' . ($lastRow+1),"=SUM(H2:H{$lastRow})");
                $sheet->setCellValue('I' . ($lastRow+1),"=SUM(I2:I{$lastRow})");
                $sheet->setCellValue('J' . ($lastRow+1),"=SUM(J2:J{$lastRow})");
                $lastRow++;
                $sheet->getStyle("A{$lastRow}:J{$lastRow}")->getFont()->setBold(true);
            }
        ];
    }
}
