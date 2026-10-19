<?php

namespace App\Livewire\Planilla\RegistroDiario;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Livewire\Traits\ConReporteDiarioExcel;
use App\Services\Planilla\PlanillaMensualDetalleServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaRegistroDiarioComponent extends Component
{
    use ConFechaReporteDia, LivewireAlert, ConReporteDiarioExcel, \App\Traits\HandlesAlerts;
    public $mes;
    public $anio;
    public function mount()
    {
        $this->inicializarFecha();
    }

    protected function despuesFechaModificada(string $fecha)
    {
        $fecha = Carbon::parse($this->fecha);
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }
    public function render()
    {
        return view('livewire.planilla.registro-diario.gestion-planilla-registro-diario');
    }
}