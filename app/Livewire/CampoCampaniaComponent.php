<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\CampaniaServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class CampoCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campania;
    public $campos;
    public $campoSeleccionado;
    public $hayCampaniaAnterior = false;
    public $hayCampaniaPosterior = false;
    
    protected $listeners = ['GuardarInformacion', 'confirmarEliminar', 'campaniaInsertada' => 'cargarUltimaCampania'];

    public function mount($campo = null)
    {
        $this->campos = Campo::orderBy('orden')->get();
        if ($campo) {
            $this->campoSeleccionado = $campo;
            Session::put('campoSeleccionado', $campo);
            $this->cargarUltimaCampania();
        } else {
            $this->campoSeleccionado = Session::get('campoSeleccionado', null);
            $this->cargarUltimaCampania();
        }

        $this->actualizarEstadoBotones();
    }
    public function updatedCampoSeleccionado()
    {
        Session::put('campoSeleccionado', $this->campoSeleccionado);
        $this->cargarUltimaCampania();
        $this->actualizarEstadoBotones();
    }
    public function cargarUltimaCampania()
    {
        if (!$this->campoSeleccionado) {
            $this->campania = null;
            Session::forget('campoSeleccionado');
            return;
        }

        $campo = Campo::find($this->campoSeleccionado);

        if (!$campo) {
            return $this->alert('error', 'El campo no existe.');
        }

        $this->campania = $campo->campanias()->orderBy('fecha_inicio', 'desc')->first();
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
        $archivos = array_filter([
            $campania->gasto_planilla_file,
            $campania->gasto_cuadrilla_file,
            $campania->gasto_resumen_bdd_file
        ]);

        // Eliminar archivos si hay rutas válidas
        if (!empty($archivos)) {
            Storage::disk('public')->delete($archivos);
        }

        $campania->delete();
        $this->cargarUltimaCampania();
        $this->alert('success', 'Registros Eliminados Correctamente.');
    }
  
    public function anteriorCampania()
    {
        $campaniaAnterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '<', $this->campania->fecha_inicio)
            ->orderByDesc('fecha_inicio')
            ->first();

        if ($campaniaAnterior) {
            $this->campania = $campaniaAnterior;
            $this->actualizarEstadoBotones();
        }
    }

    public function siguienteCampania()
    {
        $campaniaPosterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '>', $this->campania->fecha_inicio)
            ->orderBy('fecha_inicio')
            ->first();

        if ($campaniaPosterior) {
            $this->campania = $campaniaPosterior;
            $this->actualizarEstadoBotones();
        }
    }
   

    private function actualizarEstadoBotones()
    {
        if (!$this->campania || !$this->campania->fecha_inicio) {
            $this->hayCampaniaAnterior = false;
            $this->hayCampaniaPosterior = false;
            return;
        }
    
        $this->hayCampaniaAnterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '<', $this->campania->fecha_inicio)
            ->exists();
    
        $this->hayCampaniaPosterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '>', $this->campania->fecha_inicio)
            ->exists();
    }
    
    public function render()
    {
        return view('livewire.campo-campania-component');
    }
}
