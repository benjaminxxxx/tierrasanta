<?php

namespace App\Livewire\Campo;

use App\Models\Labores;
use Livewire\Component;

class VerLaboresComponent extends Component
{
    public $mostrarFormularioLabores = false;
    public $labores;
    protected $listeners = ['verLabores'];
    public function mount(){
        $this->labores = Labores::all();
    }
    public function render()
    {
        return view('livewire.campo.ver-labores-component');
    }
    public function verLabores(){
        $this->mostrarFormularioLabores = true;
    }
}
