<?php

namespace App\Livewire\GestionProveedor;

use App\Models\TiendaComercial;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProveedoresComponent extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $search;
    public $verificadoFiltro = ''; // '', '1' (verificados), '0' (no verificados)
    public $verEliminados = false;

    protected $listeners = ['ActualizarProveedores' => '$refresh', 'eliminacionConfirmada'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingVerificadoFiltro()
    {
        $this->resetPage();
    }

    public function updatingVerEliminados()
    {
        $this->resetPage();
    }

    public function confirmarEliminacion($id)
    {
        $this->confirm('¿Está seguro que desea eliminar el registro?', [
            'onConfirmed' => 'eliminacionConfirmada',
            'data' => ['id' => $id],
        ]);
    }

    public function eliminacionConfirmada($data)
    {
        try {
            $proveedor = TiendaComercial::findOrFail($data['id']);
            $proveedor->update(['eliminado_por' => auth()->id()]);
            $proveedor->delete();
            $this->alert('success', 'Proveedor Eliminado');
        } catch (\Exception $e) {
            $this->alert('error', 'No se puede eliminar el proveedor porque está asociado a otros registros.');
        }
    }

    public function restaurarProveedor($id)
    {
        try {
            $proveedor = TiendaComercial::onlyTrashed()->findOrFail($id);
            $proveedor->restore();
            $proveedor->update(['eliminado_por' => null, 'editado_por' => auth()->id()]);
            $this->alert('success', 'Proveedor restaurado correctamente.');
        } catch (\Exception $e) {
            $this->alert('error', 'No se pudo restaurar el proveedor.');
        }
    }

    public function render()
    {
        $query = $this->verEliminados
            ? TiendaComercial::onlyTrashed()
            : TiendaComercial::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('razon_social', 'like', '%' . $this->search . '%')
                  ->orWhere('nombre_comercial', 'like', '%' . $this->search . '%')
                  ->orWhere('ruc', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->verificadoFiltro !== '') {
            $query->where('verificado', (bool) $this->verificadoFiltro);
        }

        $proveedores = $query->orderBy('razon_social')->paginate(20);

        return view('livewire.gestion-proveedor.proveedores-component', [
            'proveedores' => $proveedores,
        ]);
    }
}