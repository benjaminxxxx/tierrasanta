<?php
namespace App\Exports;

use App\Models\ReporteDiarioRiego;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteDiarioRiegoSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $fecha;

    // Constructor que acepta la fecha como parámetro
    public function __construct($fecha = null)
    {
        $this->fecha = $fecha;
    }

    public function title(): string
    {
        return 'ReporteDiarioRiego';
    }

    public function collection()
    {
        $query = ReporteDiarioRiego::orderBy('fecha')
        ->orderBy('documento')
        ->orderByRaw("CASE WHEN LOWER(tipo_labor) = 'riego' THEN 0 ELSE 1 END, tipo_labor ASC")
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
            'CAMPO',
            'HORA INICIO',
            'HORA FIN',
            'TOTAL HORAS',
            'LABOR',
            'DESCRIPCIÓN',
            'SIN HABERES'
        ];
    }

    public function map($detalles): array
    {
        return [
            $detalles->fecha,
            $detalles->documento,
            $detalles->regador,
            $detalles->campo,
            substr($detalles->hora_inicio, 0, 5),
            substr($detalles->hora_fin, 0, 5),
            substr($detalles->total_horas, 0, 5),
            $detalles->tipo_labor,
            $detalles->descripcion,
            $detalles->sh==1? 'SI' : '',
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
     
        
        $sheet->getRowDimension(1)->setRowHeight(27);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
