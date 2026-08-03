<?php

namespace App\Livewire;

use App\Models\PlanMensual;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

class PlanillaBlancoComponent extends Component
{
    use ConSelectorMes;
    public $vista = 'Proyectada';

    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }
    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista;
    }
    public function render()
    {

        return view('livewire.planilla-blanco-component');
    }
}
