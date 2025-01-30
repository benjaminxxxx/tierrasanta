<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\CampaniaServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campanias;
    public $campos;
    public $campoSeleccionado;
    protected $listeners = ['GuardarInformacion', 'confirmarEliminar', 'campaniaInsertada' => 'obtenerRegistros'];

    public function mount($campo = null)
    {
        $this->campos = Campo::orderBy('orden')->get();
        if ($campo) {
            $this->campoSeleccionado = $campo;
            $this->obtenerRegistros();
        }
    }
    public function updatedCampoSeleccionado()
    {

        $this->obtenerRegistros();
    }
    public function obtenerRegistros()
    {
        if (!$this->campoSeleccionado) {
            $this->campanias = null;
            return;
        }

        $campo = Campo::find($this->campoSeleccionado);

        if (!$campo) {
            return $this->alert('error', 'El campo no existe.');
        }

        $this->campanias = $campo->campanias()->orderBy('fecha_inicio', 'desc')->get();
    }

    public function eliminarCampania($campaniaId)
    {
        $this->alert('question', '¿Está seguro(a) que desea eliminar la campaña?', [
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
                'campaniaId' => $campaniaId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $campaniaId = $data['campaniaId'];
        $campania = CampoCampania::find($campaniaId);
        if ($campania) {
            $campaniaAnterior = CampoCampania::whereDate('fecha_inicio', '<', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
            if ($campaniaAnterior) {
                //si hay un registro anterior, debemos actualizar su fecha de fin, pero actualizaremos solo en caso haya una campaña posterior
                $campaniaPosterior = CampoCampania::whereDate('fecha_inicio', '>', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
                if ($campaniaPosterior) {
                    $fecha = Carbon::parse($campaniaPosterior->fecha_inicio)->addDay(-1);
                    $campaniaAnterior->update([
                        'fecha_fin' => $fecha
                    ]);
                } else {
                    //cuando no hay fecha siguiente o posterior, quiere decir que aun no debe haber fecha_fin
                    $campaniaAnterior->update([
                        'fecha_fin' => null
                    ]);
                }
            }
        }
        $campania->delete();
        $this->obtenerRegistros();
        $this->alert('success', 'Registros Eliminados Correctamente.');
    }
    public function actualizarGastosConsumo($campaniaId)
    {

        try {

            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->actualizarGastosyConsumos();
            $this->alert('success', 'Gastos y Consumos actualizados correctamente.');

        } catch (\Throwable $th) {

            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al Actualizar los Gastos y Consumos.');
            
        }
    }

    public function render()
    {
        return view('livewire.campo-campania-component');
    }
}
