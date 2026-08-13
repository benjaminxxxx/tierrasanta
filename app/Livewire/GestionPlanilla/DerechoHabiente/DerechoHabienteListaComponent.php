<?php

namespace App\Livewire\GestionPlanilla\DerechoHabiente;

use Livewire\Component;
use Livewire\WithPagination;
use App\Domain\DerechoHabiente\DerechoHabienteFiltroDTO;
use App\Domain\DerechoHabiente\DerechoHabienteQueryService;

class DerechoHabienteListaComponent extends Component
{
    use WithPagination;

    public string $search = '';

    protected $listeners = ['derechoHabienteGuardado' => '$refresh'];

    public function updating($property)
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    public function editar(int $empleadoId)
    {
        $this->dispatch('abrirDerechoHabienteWizard', empleadoId: $empleadoId);
    }

    public function verDetalle(int $empleadoId)
    {
        $this->dispatch('abrirDerechoHabienteDetalle', empleadoId: $empleadoId);
    }

    public function verEstadisticas()
    {
        $this->dispatch('abrirDerechoHabienteEstadisticas');
    }

    public function render()
    {
        $filtro = new DerechoHabienteFiltroDTO(search: $this->search);

        $resumen = app(DerechoHabienteQueryService::class)->listarResumenPorEmpleado($filtro);

        return view('livewire.gestion-planilla.derecho-habiente.derecho-habiente-lista-component', [
            'resumen' => $resumen,
        ]);
    }
}