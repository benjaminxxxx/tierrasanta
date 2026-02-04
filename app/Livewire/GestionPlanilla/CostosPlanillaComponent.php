<?php

namespace App\Livewire\GestionPlanilla;

use App\Services\RecursosHumanos\Planilla\PlanillaServicio;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CostosPlanillaComponent extends Component
{
    use LivewireAlert, ConSelectorMes;
    public $listaPlanilla = [];
    public function mount()
    {
        $this->cargarPlanilla(false);
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargarPlanilla();
    }
    public function cargarPlanilla($dispatch = true)
    {
        if (!$this->mes || !$this->anio) {
            return;
        }
        try {
            $this->listaPlanilla = app(PlanillaServicio::class)->listarPlanillaMensual($this->mes, $this->anio);
            if($dispatch){
                $this->dispatch('recargarCostoPlanilla',data:$this->listaPlanilla);
            }
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.costos-planilla-component');
    }
}
