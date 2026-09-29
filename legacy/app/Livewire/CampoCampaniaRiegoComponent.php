<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Services\RiegoServicio;
use Livewire\Component;

class CampoCampaniaRiegoComponent extends Component
{
    public $campaniaUnica;
    public $campania;
    public $campaniaId;
    protected $listeners = ['riegosSincronizados'=>'$refresh'];
    public function mount($campaniaId=null,$campaniaUnica=false)
    {
        $campania = CampoCampania::find($campaniaId);
        if($campania){
            $this->campaniaId = $campania->id;
            $this->campania = $campania;
        } else {
            $this->campaniaId = null;
            $this->campania = false;
        }
        $this->campaniaUnica = $campaniaUnica;
    }
    public function render()
    {
        $this->riegos = RiegoServicio::obtenerRiegosPorCampaniaId($this->campaniaId);
        return view('livewire.campo-campania-riego-component',[
            'riegos' => $this->riegos
        ]);
    }
}
