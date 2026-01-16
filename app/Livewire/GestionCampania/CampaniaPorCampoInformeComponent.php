<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\AlmacenServicio;
use App\Services\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampaniaPorCampoInformeComponent extends Component
{
    use LivewireAlert;
    public $campania;
    protected $listeners = [
        'poblacionPlantasRegistrado' => 'refrescar', 
        'evaluacionInfestacionGuardada' => 'refrescar', 
        'riegoCampaniaModificado' => 'sincronizarRiegos'
    ];
    public function mount($campania)
    {
        $this->campania = CampoCampania::find($campania);
    }
    public function refrescar()
    {
        $this->campania->refresh();
    }
    public function generarResumenNutrientesCampaniasDesdeKardex()
    {
        try {
            if (!$this->campania) {
                return;
            }
            AlmacenServicio::generarFertilizantesXCampania($this->campania->id);
            $this->refrescar();
            $this->alert('success', 'Datos actualizados desde Kardex satisfactoriamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function sincronizarRiegos()
    {
        if (!$this->campania) {
            return $this->alert('error', 'Seleccione una campaña para continuar.');
        }

        $campaniaServicio = new CampaniaServicio($this->campania->id);
        $campaniaServicio->registrarHistorialRiegos();
        $this->campania->refresh();
        $this->alert('success', 'Registro sincronizado correctamente.');
    }
    public function render()
    {
        return view('livewire.gestion-campania.campania-por-campo-informe-component');
    }
}
