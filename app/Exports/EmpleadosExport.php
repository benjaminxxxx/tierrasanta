<?php

namespace App\Exports;

use App\Models\Empleado;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmpleadosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * Retorna la colección de empleados a exportar.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Empleado::all();
    }

    /**
     * Define los encabezados del archivo Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'N°',
            'Paterno',
            'Materno',
            'Nombres',
            'DNI',
        ];
    }

    /**
     * Mapea los datos de cada fila.
     *
     * @param $empleado
     * @return array
     */
    public function map($empleado): array
    {
        static $index = 0; // Para llevar la cuenta del índice numérico

        return [
            ++$index,
            $empleado->apellido_paterno,
            $empleado->apellido_materno,
            $empleado->nombres,
            $empleado->documento
        ];
    }

    /**
     * Aplica estilos al archivo Excel.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:E1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '056A70', // Fondo verde
                ],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // Texto blanco
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Centrado vertical en el header
            ],
        ]);

        // Aplicar bordes a todas las celdas
        $sheet->getStyle('A1:E' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
