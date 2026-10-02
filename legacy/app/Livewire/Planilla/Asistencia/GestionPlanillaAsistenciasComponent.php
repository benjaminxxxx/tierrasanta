<?php

namespace App\Livewire\Planilla\Asistencia;

use Livewire\Attributes\Title;use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

#[Title('Asistencia Mensual')]
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
        return view('livewire.planilla.asistencia.gestion-planilla-asistencias');
    }
}