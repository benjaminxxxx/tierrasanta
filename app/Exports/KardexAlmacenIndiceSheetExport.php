<?php
namespace App\Exports;

use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KardexAlmacenIndiceSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'INDICE';
    }

    public function collection()
    {
        return Cuadrillero::all();
    }

    public function headings(): array
    {
        return [
            'N°',
            'Nombres',
            'Grupo',
            'DNI',
            'Identificador'
        ];
    }

    public function map($cuadrillero): array
    {
        static $index = 0;

        $grupoColor = $cuadrillero->grupoCuadrilla->color ?? '#FFFFFF';

        return [
            ++$index,
            $cuadrillero->nombre_completo,
            $cuadrillero->codigo_grupo,
            $cuadrillero->dni,
            $cuadrillero->codigo,
            $grupoColor
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
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

        $sheet->getStyle('A1:E' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $grupoColor = $sheet->getCell("F{$row}")->getValue(); // Obtener el color del grupo desde la columna O
           
      
            $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => ltrim($grupoColor, '#'), // Quitar el '#' si existe
                    ],
                ],
            ]);
          
        }

        // Eliminar las columnas auxiliares
        $sheet->removeColumn('F');

        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);

        // Ajustar las dimensiones de las filas y columnas
        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        
        
        return [];
    }
}
