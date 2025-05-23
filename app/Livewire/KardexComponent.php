<?php

namespace App\Livewire;

use App\Models\Kardex;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexComponent extends Component
{
    use LivewireAlert;
    public $kardexLista = [];
    protected $listeners = ['kardexRegistrado' => '$refresh', 'confirmarEliminar'];

    public function preguntarEliminarKardex($kardexId)
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
        $kardex = Kardex::find($data['kardexId']);

        if (!$kardex) {
            $this->alert('error', 'Registro no encontrado.');
            return;
        }

        if ($kardex->productos()->exists()) {
            $kardex->update(['eliminado' => true]); // Solo marcamos como eliminado si tiene productos
            $this->alert('warning', 'No se puede eliminar permanentemente porque tiene productos. Marcado como eliminado.');
        } else {
            $kardex->delete(); // Eliminación real si no tiene productos
            $this->alert('success', 'Registro eliminado permanentemente.');
        }
    }

    public function render()
    {
        $this->kardexLista = Kardex::where('eliminado', false)->orderBy('fecha_inicial', 'desc')->get();
        return view('livewire.kardex-component');
    }
}
