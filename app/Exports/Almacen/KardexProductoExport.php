<?php

namespace App\Exports\Almacen;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KardexProductoExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            'etalleRiego' => new KardexProductoSheetExport($this->data),
        ];
    }
}
