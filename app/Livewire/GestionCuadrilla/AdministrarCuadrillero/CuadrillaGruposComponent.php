<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\CuaGrupo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CuadrillaGruposComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
    public $verEliminados;
    protected $listeners = ['confirmarEliminar', 'grupoRegistrado' => '$refresh'];
    public function restaurar($codigo)
    {
        CuaGrupo::where('codigo', $codigo)->restore();
        $this->resetPage();
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function updatedVerEliminados()
    {
        $this->resetPage();
    }
    public function eliminarGrupoCuadrilla($codigo)
    {
        try {
            CuaGrupo::where('codigo', $codigo)->delete();
            $this->resetPage();
            $this->alert('success', 'Registro Eliminado Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $query = CuaGrupo::orderByDesc('created_at');

        // Si se deben mostrar los eliminados (soft deleted)
        if ($this->verEliminados) {
            $query->onlyTrashed();
        }

        $grupos = $query->paginate(10);

        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupos-component', [
            'grupos' => $grupos,
        ]);
    }
}
