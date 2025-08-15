<?php

namespace App\Livewire\GestionCampania;
use App\Models\Campo;
use App\Models\CampoCampania;
use Livewire\Component;

class GestionCampaniaCostosDetalle extends Component
{
    public $campaniaSeleccionada;
    public $campoSeleccionado;
    public $campanias = [];
    public function mount($campo,$campaniaId=null){
        $this->campoSeleccionado = $campo;
        $this->campaniaSeleccionada = $campaniaId;
    }
    public function render(){
        return view('livewire.gestion-campania.gestion-campania-costos-detalle');
    }
}