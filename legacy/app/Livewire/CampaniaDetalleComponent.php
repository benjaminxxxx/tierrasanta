<?php

namespace App\Livewire;

use App\Models\CampoCampania as Campania;
use App\Services\CampaniaServicio;
use Livewire\Component;
use Session;

class CampaniaDetalleComponent extends Component
{
    public $campania;
    public $mostrarFormulario = false;
    protected $listeners = ['abrirCampaniaDetalle'];
    public $mostrarVacios;
    public function mount(){
        $this->mostrarVacios = Session::get('mostrarVacios',false);
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
