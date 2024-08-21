<?php
namespace App\Exports;

use App\Models\Empleado;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmpleadosSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'Empleados';
    }

    public function collection()
    {
        return Empleado::with(['grupo', 'descuento']) // Aseguramos que el grupo y descuento se carguen con los empleados
            ->orderBy('status')
            ->orderBy('grupo_codigo', 'desc')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get();
    }

    public function headings(): array
    {
        return [
            'N°',
            'Paterno',
            'Materno',
            'Nombres',
            'DNI',
            'F. Ingreso',
            'F. Nac.',
            'Cargo',
            'Desc. SP',
            'Género',
            'Salario',
            'Grupo',
            'Comp. Vac.',
            'Jubilado?',
            'Estado',
            'Color Grupo',
            'Color Descuento'
        ];
    }

    public function map($empleado): array
    {
        static $index = 0;

        $grupoColor = $empleado->grupo->color ?? '#FFFFFF';
        $descuentoColor = $empleado->descuento->color ?? '#000000';

        return [
            ++$index,
            $empleado->apellido_paterno,
            $empleado->apellido_materno,
            $empleado->nombres,
            $empleado->documento,
            $empleado->fecha_ingreso ? $empleado->fecha_ingreso : '',
            $empleado->fecha_nacimiento ? $empleado->fecha_nacimiento : '',
            $empleado->cargo->nombre ?? 'OBRERO',
            $empleado->descuento_sp_id ?? '',
            $empleado->genero ?? '',
            $empleado->salario ? (float)$empleado->salario : 0,
            $empleado->grupo_codigo ?? '',
            $empleado->compensacion_vacacional ?? '',
            (int)$empleado->esta_jubilado == 1 ? 'SI' : 'NO',
            $empleado->status,
            $grupoColor,
            $descuentoColor
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:O1')->applyFromArray([
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

        $sheet->getStyle('A1:O' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $grupoColor = $sheet->getCell("P{$row}")->getValue(); // Obtener el color del grupo desde la columna O
            $descuentoColor = $sheet->getCell("Q{$row}")->getValue(); // Obtener el color del descuento desde la columna P
            $compensacionSalarial = $sheet->getCell("M{$row}")->getValue();
            $estaJubilado = $sheet->getCell("N{$row}")->getValue();
            $grupoNombre = $sheet->getCell("L{$row}")->getValue();
            // Aplicar color de fondo al grupo
            if((int)$compensacionSalarial>0 || $grupoNombre=='PLAANT'){
                $sheet->getStyle("L{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => ltrim($grupoColor, '#'), // Quitar el '#' si existe
                        ],
                    ],
                ]);
                $sheet->getStyle("B{$row}:D{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => ltrim($grupoColor, '#'), // Quitar el '#' si existe
                        ],
                    ],
                ]);
            }
            // Aplicar color y formato en negrita al descuento
            if (!empty($descuentoColor)) {
                $sheet->getStyle("I{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => ltrim($descuentoColor, '#')], // Quitar el '#' si existe
                    ],
                ]);
            }
            if($estaJubilado == 'SI'){
                $sheet->getStyle("B{$row}:D{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFF00',
                        ],
                    ],
                ]);
            }
        }

        // Eliminar las columnas auxiliares
        $sheet->removeColumn('P');
        $sheet->removeColumn('P'); // Es importante eliminar las columnas auxiliares en el orden correcto

        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(35);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(30);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(15);

        // Ajustar las dimensiones de las filas y columnas
        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('O')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
