<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use Livewire\Component;
use Livewire\WithPagination;

class CampaniasComponent extends Component
{
    use WithPagination;
    public $campoSeleccionado;
    public $campaniaSeleccionado;
    protected $listeners = ['campaniaInsertada'=>'refrescar'];
    public function updatedCampoSeleccionado(){
        $this->resetPage();
    }
    public function updatedCampaniaSeleccionado(){
        $this->resetPage();
    }
    public function refrescar(){
        $this->resetPage();
    }
    public function render()
    {
        $query = CampoCampania::query();
        if($this->campoSeleccionado){
            $query->where('campo', $this->campoSeleccionado);
        }
        if($this->campaniaSeleccionado){
            $query->where('nombre_campania', $this->campaniaSeleccionado);
        }
        $campanias = $query->orderBy('nombre_campania','desc')
        ->orderBy('campo')
        ->orderBy('fecha_inicio','desc')
        ->paginate(20);
        
        return view('livewire.campanias-component',[
            'campanias'=>$campanias
        ]);
    }
}
