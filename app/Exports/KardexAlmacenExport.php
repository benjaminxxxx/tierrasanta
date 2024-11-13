<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KardexAlmacenExport implements WithMultipleSheets
{
    protected $productos;

    public function __construct(array $productos)
    {
        $this->productos = $productos;
    }

    /**
     * Devuelve las hojas a exportar.
     *
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $tipoActual = null;
        $contador = 1;

        // Agregar la hoja de índice principal
        $sheets['Indice'] = new KardexAlmacenIndiceSheetExport();

        // Iterar sobre el array de productos y generar las hojas según el tipo
        foreach ($this->productos as $producto) {
            $tipo = $producto['tipo'];
            $id = $producto['id'];
        
            // Reiniciar el contador si cambia el tipo
            if ($tipo !== $tipoActual) {
                $contador = 1;
                $tipoActual = $tipo;
            }
        
            // Generar un nombre de hoja único según el tipo y el contador
            $sheetName = "{$tipo}{$contador}";
            $sheets[$sheetName] = new KardexAlmacenSheetExport($id,$sheetName);
        
            // Incrementar el contador para el próximo producto del mismo tipo
            $contador++;
        }

        return $sheets;
    }
}
