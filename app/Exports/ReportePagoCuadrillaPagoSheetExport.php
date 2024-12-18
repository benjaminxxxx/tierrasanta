<?php
namespace App\Exports;

use App\Models\AsignacionFamiliar;
use App\Models\PagoCuadrilla;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportePagoCuadrillaPagoSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private $fechaInicio;
    private $fechaFin;

    public function __construct(array $data)
    {
        $this->fechaInicio = $data['fecha_inicio'];
        $this->fechaFin = $data['fecha_fin'];
    }

    public function title(): string
    {
        return 'PAGOS';
    }

    public function collection()
    {
        return PagoCuadrilla::whereDate('fecha_inicio', $this->fechaInicio)
            ->whereDate('fecha_fin', $this->fechaFin)
            ->orderBy('cuadrillero_id', 'asc')
            ->orderBy('fecha_pago', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }
    public function headings(): array
    {

        return [
            ['REGISTRO DE PAGOS DE CUADRILLEROS'],
            [],
            ['FECHA INICIAL: ' . $this->fechaInicio->format('Y-m-d')],
            ['FECHA FINAL: ' . $this->fechaFin->format('Y-m-d')],
            [],
            [],
            [
                'N°',
                'Fecha de Pago',
                'Documento',
                'Cuadrillero',
                'Monto Pagado',
                'Saldo Pendiente',
                'Fecha Contable',
                'Estado',
            ]
        ];
    }

    public function map($pagoCuadrilla): array
    {
        static $index = 0;
        return [
            ++$index,
            $pagoCuadrilla->fecha_pago,
            $pagoCuadrilla->cuadrillero->dni,
            $pagoCuadrilla->cuadrillero->nombres,
            $pagoCuadrilla->monto_pagado,
            $pagoCuadrilla->saldo_pendiente,
            $pagoCuadrilla->fecha_contable,
            $pagoCuadrilla->estado_detalle,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo de encabezados
        $sheet->getStyle('A7:H7')->applyFromArray([
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

        // Bordes para todas las celdas
        $sheet->getStyle('A7:H' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '056A70'],
                ],
            ],
        ]);

        // Centrar contenido de columnas específicas
        $sheet->getStyle('A7:H'. ($sheet->getHighestRow()))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Aplicar formato de moneda a las columnas de montos
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('"S/" #,##0.00;[Red]"S/" -#,##0.00;"S/" "-"');
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('"S/" #,##0.00;[Red]"S/" -#,##0.00;"S/" "-"');

        // Ajustar altura de la fila de encabezados
        $sheet->getRowDimension(7)->setRowHeight(27);

        return [];
    }
}
