<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CampoConsumoExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new CampoConsumoSheetExport($this->data),
        ];
    }
}
