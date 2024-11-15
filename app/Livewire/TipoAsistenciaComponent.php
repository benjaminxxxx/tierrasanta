<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\TipoAsistencia;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;

class TipoAsistenciaComponent extends Component
{
    use LivewireAlert;
    public $color;
    public $tipoAsistencias;
    protected $listeners = ["confirmarEliminar", 'resturar','nuevoRegistro'=>'$refresh'];

 
    public function eliminarTipoAsistencia($id)
    {

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', 
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'id' => $id,
            ],
        ]);
    }

    public function confirmarEliminar($data)
    {
        try {
            $tipoAsistencia = TipoAsistencia::findOrFail($data['id']);
            $tipoAsistencia->delete();
            $this->alert('success', '¡Tipo de asistencia eliminado con éxito!');

        } catch (QueryException $e) {
            $this->alert('error', 'Hubo un error al eliminar: ' . $e->getMessage());
        }
    }

    public function preguntarRestaurar(){
        $this->alert('question', 'Está a punto de restaurar los valores por defecto, ¿desea continuar?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Resturar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'resturar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function resturar(){
        try {
            TipoAsistencia::truncate();

            Artisan::call('db:seed', [
                '--class' => 'TipoAsistenciaSeeder'
            ]);
            $this->resetInputFields();
            $this->dispatch("setColorEdit");
            $this->alert("success","Registro Restaurado con Éxito");
        } catch (\Throwable $th) {
            $this->alert("error",$th->getMessage());
        }
    }
    public function render()
    {
        $this->tipoAsistencias = TipoAsistencia::all();
        return view('livewire.tipo-asistencia-component');
    }
}
