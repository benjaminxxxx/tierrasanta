<?php

namespace App\Livewire\GestionProveedor;

use App\Models\Proveedor;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProveedoresComponent extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $search = '';
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
            $proveedor = Proveedor::findOrFail($data['id']);
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
            $proveedor = Proveedor::onlyTrashed()->findOrFail($id);
            $proveedor->restore();
            $proveedor->update(['eliminado_por' => null, 'editado_por' => auth()->id()]);
            $this->alert('success', 'Proveedor restaurado correctamente.');
        } catch (\Exception $e) {
            $this->alert('error', 'No se pudo restaurar el proveedor.');
        }
    }

    public function render()
    {
        // 1. Cargar la relación 'persona' para evitar N+1 queries
        $query = Proveedor::with('persona');

        // 2. Control de SoftDeletes
        if ($this->verEliminados) {
            $query->onlyTrashed();
        }

        // 3. Filtro de Búsqueda sobre el modelo Persona relacionado
        if ($this->search) {
            $search = $this->search;
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('nombre_mostrar', 'like', "%{$search}%")
                  ->orWhere('razon_social', 'like', "%{$search}%")
                  ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        // 4. Filtro por estado de verificación
        if ($this->verificadoFiltro !== '') {
            $query->where('verificado', (bool) $this->verificadoFiltro);
        }

        // 5. Ordenamiento mediante Join por el campo 'nombre_mostrar' de Persona
        $proveedores = $query->join('personas', 'proveedores.persona_id', '=', 'personas.id')
            ->select('proveedores.*')
            ->orderBy('personas.nombre_mostrar', 'asc')
            ->paginate(20);

        return view('livewire.gestion-proveedor.proveedores-component', [
            'proveedores' => $proveedores,
        ]);
    }
}