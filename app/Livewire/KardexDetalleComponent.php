<?php

namespace App\Livewire;

use App\Models\Kardex;
use App\Models\Producto;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Session;
use Livewire\Component;


class KardexDetalleComponent extends Component
{
    use LivewireAlert;

    public $kardexId;
    public $kardex;
    public $search;
    public $resultado;
    public $producto;
    public $productoSeleccionadoId;
    public $verBlanco = false;
    protected $listeners = ['kardexProductoRegistrado' => 'listarKardex', 'importacionRealizada' => 'listarKardex', 'eliminacionConfirmar','seleccionarProducto'];
    public function mount()
    {
        if ($this->kardexId) {
            $this->kardex = Kardex::find($this->kardexId);
            $productoCargado = Session::get("producto_seleccionado_id_{$this->kardexId}");
            if ($productoCargado) {
                $this->seleccionarProducto($productoCargado);
            }
        }
    }

    public function quitarProducto()
    {
        $this->producto = null;
        $this->search = null;
        $this->productoSeleccionadoId = null;
        if(!$this->kardexId){
            return;
        }
        Session::forget("producto_seleccionado_id_{$this->kardexId}");
    }
    public function seleccionarProducto($productoId)
    {
        if (!$this->kardexId) {
            return;
        }

        $producto = Producto::find($productoId);

        if ($producto) {
            $this->resultado = null;
            $this->producto = $producto;
            $this->productoSeleccionadoId = $producto->id;

            // Guardamos en una sesión con clave única por kardexId
            Session::put("producto_seleccionado_id_{$this->kardexId}", $producto->id);
        }
    }

    public function updatedSearch()
    {
        $this->resultado = Producto::where(function ($query) {
            // Filtrar por nombre_comercial
            $query->where('nombre_comercial', 'like', '%' . $this->search . '%')
                // Filtrar también por ingrediente_activo
                ->orWhere('ingrediente_activo', 'like', '%' . $this->search . '%');
        })
            ->take(10)
            ->get();
    }


    public function render()
    {
        return view('livewire.kardex-detalle-component');
    }
}
