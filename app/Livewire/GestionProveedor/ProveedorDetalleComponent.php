<?php

namespace App\Livewire\GestionProveedor;

use App\Models\Proveedor;
use Livewire\Component;

class ProveedorDetalleComponent extends Component
{
    public bool $mostrarDetalle = false;
    public ?Proveedor $proveedor = null;

    protected $listeners = ['verDetalleProveedor'];

    public function verDetalleProveedor($id): void
    {
        $this->proveedor = Proveedor::withTrashed()
            ->with('persona')
            ->find($id);

        $this->mostrarDetalle = true;
    }

    public function render()
    {
        return view('livewire.gestion-proveedor.proveedor-detalle-component');
    }
}