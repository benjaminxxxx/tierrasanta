<?php

namespace App\Livewire;

use App\Models\Labores;
use Livewire\Component;

class VerLaboresComponent extends Component
{
    public $isFormOpen = false;
    public $labores;
    public function mount(){
        $this->labores = Labores::all();
    }
    public function render()
    {
        return view('livewire.ver-labores-component');
    }
    public function verLabores(){
        $this->isFormOpen = true;
    }
}
