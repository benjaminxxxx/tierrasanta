<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaAsistenciasComponent extends Component
{
    use LivewireAlert, ConSelectorMes;
    protected $listeners = ['mes-actualizado' => 'actualizarFecha'];
    public function mount(){
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio){
       
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-asistencias');
    }
}