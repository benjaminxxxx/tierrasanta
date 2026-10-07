<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Almacen\AlmacenServicio;
use App\Services\Campania\CampaniaHistorialServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampaniaPorCampoInformeComponent extends Component
{
    use LivewireAlert;
    public $campania;
    protected $listeners = [
        'poblacionPlantasRegistrado' => 'refrescar', 
        'evaluacionInfestacionGuardada' => 'refrescar',
        'brotesPorPisoRegistrado' => 'refrescar',
        'riegoCampaniaModificado' => 'sincronizarRiegos',
        'campaniaInsertada' => 'refrescar', 
        'refrescarInformeCampaniaXCampo' => 'refrescar',
        'siembraGuardada' => 'refrescar',
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
        $campaniaServicio = new CampaniaHistorialServicio($this->campania->id);
        $campaniaServicio->registrarHistorialRiegos();
        $this->campania->refresh();
        $this->alert('success', 'Registro sincronizado correctamente.');
    }
    public function render()
    {
        return view('livewire.campania.campania-por-campo-informe-component', [
            // En qué etapa está la campaña y qué evaluaciones de brotes le tocarían
            'lineaTiempo' => $this->campania ? app(\App\Services\Campania\Etapa\CampaniaEtapaConsulta::class)->lineaDeTiempo($this->campania) : null,
        ]);
    }
}
