<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaCuadrillerosComponent extends Component
{
    use LivewireAlert;
    public $cuadrilleros;
    public $verEliminados = false;
    protected $listeners = ['cuadrilleroRegistrado'=>'$refresh','confirmarEliminar'];
    public function confirmarEliminarCuadrillero($id)
    {

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'cuadrilleroId' => $id,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $cuadrilleroId = $data['cuadrilleroId'];
        Cuadrillero::find($cuadrilleroId)->update(['estado'=>false]);
        $this->alert('success', 'Registro eliminado correctamente.');
    }
    public function restaurar($cuadrilleroId){
        Cuadrillero::find($cuadrilleroId)->update(['estado'=>true]);
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function render()
    {
        $this->cuadrilleros = Cuadrillero::orderBy('nombres')->where('estado',!$this->verEliminados)->get();
        return view('livewire.cuadrilla-cuadrilleros-component');
    }
}
