<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CampoGastoPlanillaExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new CampoGastoPlanillaSheetExport($this->data),
        ];
    }
}
