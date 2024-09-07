<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CuadrillerosExport implements WithMultipleSheets
{
    /**
     * Devuelve las hojas a exportar.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            'Cuadrilleros' => new CuadrillerosSheetExport()
        ];
    }
}
