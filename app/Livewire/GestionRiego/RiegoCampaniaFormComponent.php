<?php

namespace App\Livewire\GestionRiego;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;

class RiegoCampaniaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioRiegoCampania = false;
    public $riego_descarga_ha_hora;
    public $campania;
    protected $listeners = ['editarRiegoCampania'];

    public function mount()
    {

    }
   
    public function editarRiegoCampania($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            $this->alert('error', 'Campaña no encontrada');
            return;
        }
        $this->campania = $campania;
        $this->riego_descarga_ha_hora = $campania->riego_descarga_ha_hora;
        $this->mostrarFormularioRiegoCampania = true;
    }
    public function guardarRiegoCampania()
    {
        try {

            $this->campania->riego_descarga_ha_hora = $this->riego_descarga_ha_hora ?? null;
            $this->campania->save();

            $this->alert('success', 'Parámetro guardado correctamente');
            $this->mostrarFormularioRiegoCampania = false;
            $this->dispatch('riegoCampaniaModificado');

        } catch (Exception $e) {
            $this->alert('error',  $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-riego.riego-campania-form-component');
    }
}