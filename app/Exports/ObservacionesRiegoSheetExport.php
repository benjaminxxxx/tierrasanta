<?php
namespace App\Exports;

use App\Models\Observacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ObservacionesRiegoSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $fecha;

    // Constructor que acepta la fecha como parámetro
    public function __construct($fecha = null)
    {
        $this->fecha = $fecha;
    }

    public function title(): string
    {
        return 'ObservacionesRiego';
    }

    public function collection()
    {
        $query = Observacion::orderBy('fecha')
            ->orderBy('documento', 'desc')
            ->orderBy('hora_inicio');

        if ($this->fecha) {
            $query->whereDate('fecha', $this->fecha);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'DNI',
            'REGADOR',
            'HORA INICIO',
            'HORA FIN',
            'TOTAL HORAS',
            'DETALLE OBSERVACION',
            'TIPO DE EMPLEADO',
        ];
    }

    public function map($detalles): array
    {
        return [
            $detalles->fecha,
            $detalles->documento,
            $detalles->nombre_regador,
            $detalles->hora_inicio,
            $detalles->hora_fin,
            $detalles->horas,
            $detalles->detalle_observacion,
            $detalles->tipo_empleado,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
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

        $sheet->getStyle('A1:H' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);


        $sheet->getRowDimension(1)->setRowHeight(27);
        /*
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);*/

        // Ajustar las dimensiones de las filas y columnas
        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);



        return [];
    }
}