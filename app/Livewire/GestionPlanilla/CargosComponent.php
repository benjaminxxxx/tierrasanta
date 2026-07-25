<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanCargo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CargosComponent extends Component
{
    use WithPagination, LivewireAlert;

    public array $breadcrumb = [];
    public bool $verEliminados = false;
    public string $busqueda = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';

    protected $listeners = ['cargoGuardado' => '$refresh'];
    public function mount()
    {
        $this->breadcrumb = [
            ['label' => 'Cargos'],
        ];
    }

    public function updatingVerEliminados(): void
    {
        $this->resetPage();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        $this->dispatch('crearCargo');
    }

    public function editar(int $cargoId): void
    {
        $this->dispatch('editarCargo', cargoId: $cargoId);
    }

    public function toggleActivo(int $cargoId): void
    {
        $cargo = PlanCargo::findOrFail($cargoId);
        $cargo->update(['activo' => !$cargo->activo]);
    }

    public function eliminar(int $cargoId): void
    {
        $cargo = PlanCargo::withCount('contratoCargos')->findOrFail($cargoId);

        if ($cargo->contrato_cargos_count > 0) {
            $this->alert('error', 'No se puede eliminar: el cargo tiene historial de contratos asociados.');
            return;
        }

        $cargo->update(['eliminado_por' => auth()->user()?->name]);
        $cargo->delete();

        $this->alert('success', 'Cargo eliminado.');
    }

    public function restaurar(int $cargoId): void
    {
        PlanCargo::onlyTrashed()->findOrFail($cargoId)->restore();
        $this->alert('success', 'Cargo restaurado.');
    }

    public function eliminarDefinitivo(int $cargoId): void
    {
        $cargo = PlanCargo::onlyTrashed()->withCount('contratoCargos')->findOrFail($cargoId);

        if ($cargo->contrato_cargos_count > 0) {
            $this->alert('error', 'No se puede eliminar definitivamente: tiene historial asociado.');
            return;
        }

        $cargo->forceDelete();
        $this->alert('success', 'Cargo eliminado definitivamente.');
    }
    public function buscar()
    {
        $this->resetPage();
    }
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    public function render()
    {
        $cargos = PlanCargo::query()
            ->withCount([
                'empleadoCargosVigentes as trabajadores_actuales',
                'empleadoCargos as total_relaciones',
            ])
            ->when($this->verEliminados, fn($q) => $q->onlyTrashed())
            ->when($this->busqueda !== '', fn($q) => $q->where('nombre', 'like', "%{$this->busqueda}%"))
            ->orderBy($this->sortField,$this->sortDirection)
            ->paginate(10);

        return view('livewire.gestion-planilla.cargos-component', compact('cargos'));
    }
}