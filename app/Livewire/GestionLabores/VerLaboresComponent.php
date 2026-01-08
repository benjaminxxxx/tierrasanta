<?php

namespace App\Livewire\GestionLabores;

use App\Models\Labores;
use Livewire\Component;

class VerLaboresComponent extends Component
{
    public $mostrarFormularioLabores = false;
    public $labores;
    public function mount(){
        $this->labores = Labores::all();
    }
    public function render()
    {
        return view('livewire.gestion-labores.ver-labores-component');
    }
    public function verLabores(){
        $this->mostrarFormularioLabores = true;
    }
}
