<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmpleadosExport implements WithMultipleSheets
{
    /**
     * Devuelve las hojas a exportar.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            'Empleados' => new EmpleadosSheetExport(),
            'AsignacionFamiliar' => new AsignacionFamiliarExport()
        ];
    }
}
