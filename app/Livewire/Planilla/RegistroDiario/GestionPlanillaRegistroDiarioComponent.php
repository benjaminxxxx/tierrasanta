<?php

namespace App\Livewire\Planilla\RegistroDiario;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Services\Planilla\Modulos\GestionPlanillaReporteDiario;
use App\Services\Planilla\PlanillaMensualDetalleServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaRegistroDiarioComponent extends Component
{
    use ConFechaReporteDia, LivewireAlert;
    public $listaPlanilla = [];
    public $mes;
    public $anio;
    public $mostrarListaPlanillaMensual = false;
    public function mount()
    {
        $this->inicializarFecha();
    }
    public function gestionarListaMensual()
    {
        try {

            $this->listaPlanilla = app(GestionPlanillaReporteDiario::class)->obtenerPlanillaMensualXFecha($this->fecha)->toArray();
            $this->mostrarListaPlanillaMensual = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
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