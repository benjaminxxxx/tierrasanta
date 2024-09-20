<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RiegosExport implements WithMultipleSheets
{
    protected $fecha;

    // Constructor que acepta la fecha como parámetro
    public function __construct($fecha = null)
    {
        $this->fecha = $fecha;
    }

    /**
     * Devuelve las hojas a exportar.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            'DetalleRiego' => new DetalleRiegoSheetExport($this->fecha),
            'Observaciones' => new ObservacionesRiegoSheetExport($this->fecha)
        ];
    }
}
