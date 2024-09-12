<?php

namespace App\Livewire;

use App\Models\Campo;
use Livewire\Component;

class SeleccionarCamposComponent extends Component
{
    public $abrirSeleccionarCampos = false;
    public $campos;
    public $documento;
    public $camposSeleccionados=[];
    protected $listeners = ['abrirParaSeleccionarCampos'];
    public function mount(){
        $this->campos = Campo::with('hijos')->orderBy('grupo')->orderBy('orden')->whereNull('campo_parent_nombre')->get();
    }
    public function render()
    {
        return view('livewire.seleccionar-campos-component');
    }
    public function abrirParaSeleccionarCampos($documento,$campos){
       
        $this->camposSeleccionados = array_map(function($campo) {
            return $campo['nombre'];
        }, $campos);

        $this->documento = $documento;
        $this->abrirSeleccionarCampos = true;
    }
    public function guardarSeleccion(){
     
        $this->dispatch('camposSeleccionados',[
            'documento'=>$this->documento,
            'campos'=>$this->camposSeleccionados
        ]);
        $this->camposSeleccionados=[];
        $this->abrirSeleccionarCampos = false;
    }
}
