<?php

namespace App\Livewire;

use App\Models\Kardex;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexComponent extends Component
{
    use LivewireAlert;
    public $kardexLista = [];
    protected $listeners = ['kardexRegistrado'=>'$refresh','confirmarEliminar'];
   
    public function preguntarEliminar($kardexId)
    {

        $this->alert('question', '¿Está seguro(a) que desea eliminar el Kardex?', [
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
                'kardexId' => $kardexId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        Kardex::find($data['kardexId'])->update(['eliminado'=>true]);
        $this->alert('success', 'Registro Eliminado Correctamente.');
    }
    public function render()
    {
        $this->kardexLista = Kardex::where('eliminado',false)->orderBy('fecha_inicial','desc')->get();
        return view('livewire.kardex-component');
    }
}
