<?php

namespace App\Livewire;

use App\Models\LaboresRiego;
use Livewire\Component;

class LaboresRiegoComponent extends Component
{
    public $labores;
    public $nuevaLabor;
    public function render()
    {
        $this->labores = LaboresRiego::all();

        return view('livewire.labores-riego-component');
    }
    public function agregarLabor(){

        if(!$this->nuevaLabor){
            return;
        }

        LaboresRiego::create([
            'nombre_labor'=>$this->nuevaLabor
        ]);

        $this->nuevaLabor = null;
    }
    public function eliminarLabor($id){
        LaboresRiego::find($id)->delete();
    }
}
