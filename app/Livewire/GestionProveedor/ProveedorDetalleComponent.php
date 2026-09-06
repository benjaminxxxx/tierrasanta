<?php

namespace App\Livewire\GestionProveedor;

use App\Models\TiendaComercial;
use Livewire\Component;

class ProveedorDetalleComponent extends Component
{
    public $mostrarDetalle = false;
    public $proveedor;

    protected $listeners = ['verDetalleProveedor'];

    public function verDetalleProveedor($id)
    {
        $this->proveedor = TiendaComercial::withTrashed()->find($id);
        $this->mostrarDetalle = true;
    }

    public function render()
    {
        return view('livewire.gestion-proveedor.proveedor-detalle-component');
    }
}