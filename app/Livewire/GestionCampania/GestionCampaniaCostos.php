<?php

namespace App\Livewire\GestionCampania;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use Livewire\Component;
use Session;

class GestionCampaniaCostos extends Component
{
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $campanias = [];
    public $campos = [];
    public $registrosTotales = [];
    public function mount(){
        $this->campoSeleccionado = Session::get('campo_seleccionado');
        $this->campos = Campo::with(['campanias'])->get();
        $this->obtenerCampanias();
    }
    public function updatedCampoSeleccionado(){
        $this->obtenerCampanias();
        Session::put('campo_seleccionado');
    }
    public function obtenerCampanias(){
        $this->campanias = CampoCampania::where('campo',$this->campoSeleccionado)->orderBy('fecha_inicio')->get()->toArray();
    }
    public function detectarCostos($campaniaId){
        try {
            
            $costosManoObra = CampaniaServicio::obtenerCostosManoObra($campaniaId);
            dd($costosManoObra);
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
    public function render(){
        return view('livewire.gestion-campania.gestion-campania-costos');
    }
}