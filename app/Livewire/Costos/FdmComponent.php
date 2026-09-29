<?php

namespace App\Livewire\Costos;

use Livewire\Attributes\Title;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

#[Title('Costos FDM')]
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
        return view('livewire.costos.fdm-component');
    }
}
