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
            new PlanillaSheetExport($this->data),                               //HOJA PLANILLA
            new PlanillaDescuentoSheetExport($this->data['descuentosAfp']),     //HOJA DESCUENTO AFP
            new PlanillaHorasSheetExport($this->data),                          //HOJA HORAS
            new PlanillaBonosSheetExport($this->data),                          //HOJA BONOS
            new PlanillaPagoSheetExport($this->data),                           //HOJA JORNAL
            new PlanillaCostoRealSheetExport($this->data)                       //HOJA JORNAL_TOTAL_COSTO*/
        ];
    }
}
