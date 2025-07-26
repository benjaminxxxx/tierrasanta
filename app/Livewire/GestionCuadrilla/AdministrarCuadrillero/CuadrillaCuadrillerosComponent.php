<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\Cuadrillero;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CuadrillaCuadrillerosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    use WithoutUrlPagination;
    public $verEliminados = false;
    public $nombreDocumentoFiltro;
    public $grupos = [];
    public $codigo_grupo;
    protected $listeners = ['cuadrilleroRegistrado' => '$refresh', 'confirmarEliminar'];
    public function mount()
    {
        $this->grupos = CuadrilleroServicio::obtenerGrupos();
    }
    public function confirmarEliminarCuadrillero($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminar',
            'data' => [
                'cuadrilleroId' => $id,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $cuadrilleroId = $data['cuadrilleroId'];
        Cuadrillero::find($cuadrilleroId)->update(['estado' => false]);
        $this->alert('success', 'Registro eliminado correctamente.');
    }
    public function restaurar($cuadrilleroId)
    {
        Cuadrillero::find($cuadrilleroId)->update(['estado' => true]);
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function updatedVerEliminados()
    {
        $this->resetPage();
    }
    public function filtrarCuadrilleros()
    {
        $this->resetPage();
    }
    public function render()
    {
        $query = Cuadrillero::orderBy('nombres');

        if ($this->nombreDocumentoFiltro) {
            $query->where(function ($q) {
                $q->where('nombres', 'like', '%' . $this->nombreDocumentoFiltro . '%')
                    ->orWhere('dni', 'like', '%' . $this->nombreDocumentoFiltro . '%');
            });
        }
        if ($this->codigo_grupo) {
            $query->where('codigo_grupo',$this->codigo_grupo);
        }

        $query->where('estado', !$this->verEliminados);

        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-cuadrilleros-component', [
            'cuadrilleros' => $query->paginate(15)
        ]);
    }
}
