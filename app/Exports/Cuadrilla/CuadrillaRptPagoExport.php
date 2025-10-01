<?php

namespace App\Exports\Cuadrilla;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CuadrillaRptPagoExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new CuadrillaRptPagoDetalleExport($this->data),
            new CuadrillaRptPagoConsolidadoExport($this->data),
            new CuadrillaRptPagoDesgloseExport($this->data),
        ];
    }
}
