<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\Cuadrillero;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Traits\ListasComunes\ConGrupoCuadrilla;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CuadrillaCuadrillerosComponent extends Component
{
    use LivewireAlert, ConGrupoCuadrilla, WithPagination, WithoutUrlPagination;
    public $verEliminados = false;
    public $nombreDocumentoFiltro;
    public $grupoSeleccionado;
    protected $listeners = ['cuadrilleroRegistrado' => '$refresh', 'confirmarEliminar'];

    public function eliminarCuadrillero($cuadrilleroId)
    {
        Cuadrillero::find($cuadrilleroId)->delete();
        $this->resetPage();
        $this->alert('success', 'Registro eliminado correctamente.');
    }
    public function restaurar($cuadrilleroId)
    {
        Cuadrillero::withTrashed()->find($cuadrilleroId)->restore();
        $this->resetPage();
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
        $cuadrilleros = app(CuadrilleroServicio::class)->listar(
            $this->nombreDocumentoFiltro,
            $this->grupoSeleccionado,
            $this->verEliminados,
            2 // Registros por página
        );

        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-cuadrilleros-component', [
            'cuadrilleros' => $cuadrilleros,
        ]);
    }

}
