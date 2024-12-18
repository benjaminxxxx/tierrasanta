<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportePagoCuadrillaExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new ReportePagoCuadrillaSheetExport($this->data['cuadrilleros']),
            new ReportePagoCuadrillaPagoSheetExport($this->data['pagos']),
        ];
    }
}
