<?php

namespace App\Livewire;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaAsistenciaLaboresComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $semana;
    public $mostrarFormulario = false;
    public $registroId;
    public $actividades = [];
    protected $listeners = ['verLaboresComponent', 'confirmarEliminar','actividadRegistrada'=>'listarActividades'];
  
    public function verLaboresComponent($semanaId, $indice)
    {

        try {
            $this->actividades = [];
            $this->semana = CuaAsistenciaSemanal::find($semanaId);
            if (!$this->semana) {
                return $this->alert('error', 'La semana no existe.');
            }
            $fechaInicio = Carbon::parse($this->semana->fecha_inicio)->addDays($indice);
            $this->fecha = $fechaInicio->format('Y-m-d');
            $this->listarActividades();
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno.');
        }
    }
    public function listarActividades()
    {
        $this->actividades = Actividad::whereDate('fecha', $this->fecha)->get();
        if($this->semana){
            //funcion agregada que se ejecuta despues de actualizar algun registro
            $this->semana->actualizarTotales();
            //comando para una vez actualizado los totales actualizar el formulario de detalle principal
            $this->dispatch('cuadrillerosAgregadosAsistencia');
        }
    }
    
    public function preguntarEliminar($registroId)
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
                'registroId' => $registroId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        try {
            $registroId = $data['registroId'];
            Actividad::findOrFail($registroId)->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->listarActividades();
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno CALC CE.');
        }
    }
    
    public function render()
    {
        return view('livewire.cuadrilla-asistencia-labores-component');
    }
}
