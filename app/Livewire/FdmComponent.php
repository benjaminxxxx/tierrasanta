<?php

namespace App\Livewire;

use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class FdmComponent extends Component
{
    use ConSelectorMes;
    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }
    public function render()
    {
        return view('livewire.fdm-component');
    }
}
