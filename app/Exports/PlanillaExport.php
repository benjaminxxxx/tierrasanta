<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlanillaExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new PlanillaSheetExport2($this->data),
            new PlanillaDescuentoSheetExport($this->data['descuentosAfp']),
            new PlanillaHorasSheetExport($this->data),
            new PlanillaCostoRealSheetExport($this->data),
            new PlanillaPagoSheetExport($this->data),
        ];
    }
}
