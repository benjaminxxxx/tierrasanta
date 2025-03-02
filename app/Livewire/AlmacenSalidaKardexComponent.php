<?php

namespace App\Livewire;

use App\Exports\KardexAlmacenExport;
use App\Models\Producto;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class AlmacenSalidaKardexComponent extends Component
{
    protected $listeners = ['descargarKardex'];
    public function descargarKardex($mes, $anio)
    {
        $productos = Producto::with('categoria')->get();

        // Ordenar los productos primero por categoría y luego por nombre comercial
        $productosOrdenados = $productos->map(function ($producto) {
            // Obtener la primera letra de la categoría en mayúscula
            $producto->tipo = strtoupper(substr($producto->categoria, 0, 1));
            return $producto;
        })->sortBy(['tipo', 'nombre_comercial'])->values();

        // Convertir a un array adecuado para pasarlo al exportador
        $productosArray = $productosOrdenados->map(function ($producto) {
            return [
                'id' => $producto->id,
                'tipo' => $producto->tipo,
                'nombre_comercial' => $producto->nombre_comercial,
            ];
        })->toArray();

        return Excel::download(new KardexAlmacenExport($productosArray), 'kardex_almacen.xlsx');
    }
    public function render()
    {
        return view('livewire.almacen-salida-kardex-component');
    }
}
