<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\DetalleRiego;
use App\Models\PlanEmpleado;
use Carbon\Carbon;
use Livewire\Component;

class CampoMapaComponent extends Component
{
    public $campos;
    protected $listeners = ['posicionActualizada' => '$refresh'];
   
    public function render()
    {
        $this->campos = Campo::all();
        return view('livewire.campo-mapa-component');
    }
}
