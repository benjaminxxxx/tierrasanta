<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Models\Producto;
use App\Services\Almacen\InsumoKardexServicio;
use App\Services\KardexServicio;
use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class InsumoKardexFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioKardex = false;
    public $insumoKardexId;
    // ARRAY COMPUESTO
    public $kardex = [
        'producto_id' => null,
        'descripcion' => '',
        'codigo_existencia' => '',
        'anio' => '',
        'tipo' => '',
        'stock_inicial' => '',
        'costo_unitario' => '',
        'costo_total' => '',
    ];

    public $productos = [];

    #[On('nuevoInsumoKardex')]
    public function nuevoInsumoKardex()
    {
        $this->resetForm();
        $this->mostrarFormularioKardex = true;
    }

    public function mount()
    {
        // Cargar productos para el <select>
        $this->productos = Producto::orderBy('nombre_comercial')->get()->map(function ($producto){
            return [
                'id'=>$producto->id,
                'name' => $producto->nombre_comercial
            ];
        })
        ->toArray();
    }

    public function guardarKardex()
    {
        try {

            app(InsumoKardexServicio::class)->guardarInsumoKardex($this->kardex,$this->insumoKardexId);
            $this->dispatch('insumoKardexRefrescar');
            $this->mostrarFormularioKardex = false;
            $textoCreado = $this->insumoKardexId?'actualizado':'creado';
            $this->alert('success', "Kardex {$textoCreado} correctamente.");

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->resetErrorBag();

        $this->kardex = [
            'producto_id' => null,
            'descripcion' => '',
            'codigo_existencia' => '',
            'anio' => '',
            'tipo' => '',
            'stock_inicial' => '',
            'costo_unitario' => '',
            'costo_total' => '',
        ];
    }

    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-form-component');
    }
}
