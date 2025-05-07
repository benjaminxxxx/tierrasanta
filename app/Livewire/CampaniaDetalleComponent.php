<?php

namespace App\Livewire;

use App\Models\CampoCampania as Campania;
use App\Models\PoblacionPlantas;
use App\Models\Siembra;
use App\Services\CampaniaServicio;
use Livewire\Component;

class CampaniaDetalleComponent extends Component
{
    public $campania;
    public $mostrarFormulario = false;
    protected $listeners = ['abrirCampaniaDetalle'];
    public function actualizarInformacionCampania(){
        if(!$this->campania){
            return $this->alert('error','La campaña ya no existe.');
        }

        $campaniaServicio = new CampaniaServicio($this->campania->id);
        $campaniaServicio->registrarHistorialPoblacionPlantas();
        $campaniaServicio->registrarHistorialBrotes();
        $campaniaServicio->actualizarGastosyConsumos();
    }
    public function abrirCampaniaDetalle($campaniaId)
    {
        try {
            $campania = Campania::findOrFail($campaniaId);
            if ($campania) {
                $this->campania = $campania;
                $this->mostrarFormulario = true;
            } else {
                $this->alert('error', 'La campaña ya no existe.');
            }
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al buscar la campaña.');
        }
    }
    public function render()
    {
        return view('livewire.campania-detalle-component');
    }
}
