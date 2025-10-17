<?php

namespace App\Livewire\GestionPlanilla\AdministrarRegistroDiario;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Services\Modulos\Planilla\GestionPlanillaReporteDiario;
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
    public function mount(){
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
    public function agregarPlanilleros()
    {

        $empleados = app(GestionPlanillaReporteDiario::class)->obtenerPlanillaAgraria($this->mes, $this->anio)->toArray();
        
        if (count($empleados) == 0) {
            return $this->alert('warning', 'No hay registros aún');
        }
        $this->listaPlanilla = $empleados;
    }
    public function guardarOrdenMensualEmpleados()
    {
        try {
            
            app(GestionPlanillaReporteDiario::class)->guardarOrdenMensualEmpleados($this->mes, $this->anio, $this->listaPlanilla);
            $this->mostrarListaPlanillaMensual = false;
            $this->dispatch('actualizarListaPlanillaRegistroDiario');
            $this->alert('success','Registro Actualizado Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
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
        return view('livewire.gestion-planilla.administrar-registro-diario.gestion-planilla-registro-diario');
    }
}