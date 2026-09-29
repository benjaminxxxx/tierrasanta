<?php

namespace App\Livewire\Planilla\DerechoHabiente;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\Planilla\DerechoHabiente\DerechoHabienteFiltroDTO;
use App\Services\Planilla\DerechoHabiente\DerechoHabienteQueryService;

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

        return view('livewire.planilla.derecho-habiente.derecho-habiente-lista-component', [
            'resumen' => $resumen,
        ]);
    }
}