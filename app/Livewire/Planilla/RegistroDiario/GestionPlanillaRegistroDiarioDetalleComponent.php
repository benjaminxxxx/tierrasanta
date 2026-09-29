<?php

namespace App\Livewire\Planilla\RegistroDiario;

use App\Models\PlanResumenDiario;
use App\Services\Planilla\Modulos\GestionPlanillaReporteDiario;
use App\Services\Planilla\Empleado\ActividadServicio;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;
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
    public $modifiedRowIndexes = [];
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
            if(is_array($datos) && count($datos)==0){
                $this->alert('warning','Sin cambios realizados.');
                return;
            }
            $this->resumenDiarioPlanilla->update([
                'total_actividades'=>$this->totalActividades,
            ]);
            app(PlanillaRegistroDiarioServicio::class)->guardarRegistrosDiarios($this->fecha,$datos,$this->totalActividades);
            $this->obtenerResumenDiarioPlanilla();
            ActividadServicio::detectarYCrearActividades($this->fecha);
            $this->hasUnsavedChanges = false;
            $this->modifiedRowIndexes = [];
            $this->alert('success',"Registros Guardados Correctamente.");

        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.planilla.registro-diario.gestion-planilla-registro-diario-detalle');
    }
}