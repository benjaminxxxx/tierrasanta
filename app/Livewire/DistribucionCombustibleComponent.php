<?php

namespace App\Livewire;

use Livewire\Component;

class DistribucionCombustibleComponent extends Component
{
    protected $listeners = ['verDistribucionCombustublble'];
    public function verDistribucionCombustublble($salidaId){
        dd($salidaId);
    }
    public function render()
    {
        return view('livewire.distribucion-combustible-component');
    }
}
