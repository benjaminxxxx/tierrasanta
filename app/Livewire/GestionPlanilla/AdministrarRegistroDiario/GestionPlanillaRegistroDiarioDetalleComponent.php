<?php

namespace App\Livewire\GestionPlanilla\AdministrarRegistroDiario;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Models\PlanResumenDiario;
use App\Services\Modulos\Planilla\GestionPlanillaReporteDiario;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Traits\ListasComunes\ConArrayCampos;
use App\Traits\ListasComunes\ConArrayPlanTipoAsistencia;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaRegistroDiarioDetalleComponent extends Component
{
    use ConArrayPlanTipoAsistencia, ConArrayCampos, LivewireAlert;
    public $fecha;
    public $empleados = [];
    public $resumenDiarioPlanilla;
    public $totalesAsistencias = [];
    public $totalesAsistenciasCuadrilleros = 0;
    public $totalesAsistenciasPlanilleros = 0;
    public $totalActividades = 1;
    public $hasUnsavedChanges = false;
    protected $listeners = ['actualizarListaPlanillaRegistroDiario'=>'obtenerHandsonTableReporteDiario'];
    public function mount($fecha){
        $this->fecha = $fecha;
        $this->obtenerResumenDiarioPlanilla();
        $this->obtenerHandsonTableReporteDiario(false);
    }
    public function obtenerResumenDiarioPlanilla()
    {
        if (!$this->fecha) {
            return;
        }

        $this->resumenDiarioPlanilla = PlanResumenDiario::firstOrCreate(['fecha' => $this->fecha]);
        $this->totalActividades = $this->resumenDiarioPlanilla->total_actividades != 0 ? $this->resumenDiarioPlanilla->total_actividades:1;
       
    }
    public function obtenerHandsonTableReporteDiario($dispatch = true){
        try {
            $this->empleados = app(GestionPlanillaReporteDiario::class)->obtenerHandsontableObtenerRegistroDiarioPlanilla($this->fecha);
            if($dispatch){
                $this->dispatch("setEmpleados", $this->empleados);
            }
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function guardarInformacionRegistroPlanilla($datos)
    {
        if (!$this->fecha || !$this->resumenDiarioPlanilla || !is_array($datos)) {
            return;
        }

        try {
            
            $this->resumenDiarioPlanilla->update([
                'total_actividades'=>$this->totalActividades,
            ]);

            app(GestionPlanillaReporteDiario::class)->guardarRegistrosDiarios($this->fecha,$datos,$this->totalActividades);
            $this->obtenerResumenDiarioPlanilla();
            ActividadServicio::detectarYCrearActividades($this->fecha);
            $this->hasUnsavedChanges = false;
            $this->alert('success',"Registros Guardados Correctamente.");

        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-registro-diario.gestion-planilla-registro-diario-detalle');
    }
}