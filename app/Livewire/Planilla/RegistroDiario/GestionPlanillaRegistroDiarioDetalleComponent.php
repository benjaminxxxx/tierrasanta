<?php

namespace App\Livewire\Planilla\RegistroDiario;

use App\Constants\Permisos;
use App\Models\PlanResumenDiario;
use App\Services\Planilla\ListaMensual\PlanillaListaMensualProceso;
use App\Services\Planilla\ListaMensual\PlanillaListaMensualReglas;
use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use App\Services\Planilla\Suspension\PlanillaSuspensionDiarioProceso;
use Illuminate\Support\Carbon;
use App\Services\Planilla\Modulos\GestionPlanillaReporteDiario;
use App\Services\Planilla\Empleado\ActividadServicio;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;
use App\Traits\ListasComunes\ConArrayCampos;
use App\Traits\ListasComunes\ConArrayPlanTipoAsistencia;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaRegistroDiarioDetalleComponent extends Component
{
    use ConArrayPlanTipoAsistencia, ConArrayCampos, LivewireAlert, \App\Traits\HandlesAlerts;
    public $fecha;
    public $empleados = [];
    public $resumenDiarioPlanilla;
    public $totalesAsistencias = [];
    public $totalesAsistenciasCuadrilleros = 0;
    public $totalesAsistenciasPlanilleros = 0;
    public $totalActividades = 1;
    public $hasUnsavedChanges = false;
    public $modifiedRowIndexes = [];
    /** Vacaciones, descansos y permisos que cubren la fecha (aviso arriba de la tabla) */
    public $ausencias = [];
    /** El mes no tiene lista y no se generó (sin contratados agrarios o mes lejano) */
    public $sinLista = false;
    protected $listeners = ['actualizarListaPlanillaRegistroDiario'=>'obtenerHandsonTableReporteDiario'];
    public function mount($fecha){
        $this->fecha = $fecha;
        $this->prepararListaMensual();
        $this->obtenerResumenDiarioPlanilla();
        $this->obtenerHandsonTableReporteDiario(false);
        $this->cargarAusencias();
    }

    /** El mes se genera solo (orden del mes anterior, contratados nuevos al final): ya no hace falta "Actualizar contratados". */
    private function prepararListaMensual(): void
    {
        $f = Carbon::parse($this->fecha);
        try {
            if (!PlanillaListaMensualProceso::existe($f->month, $f->year) && PlanillaListaMensualReglas::sePuedeGenerar($f->month, $f->year)) {
                app(PlanillaListaMensualProceso::class)->generar($f->month, $f->year);
            }
            $this->sinLista = !PlanillaListaMensualProceso::existe($f->month, $f->year);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function cargarAusencias(): void
    {
        $this->ausencias = $this->fecha ? app(PlanillaSuspensionConsulta::class)->vigentesEn($this->fecha) : [];
    }

    /** Regresó antes: su suspensión termina ayer y se quitan sus V (DM…) desde hoy hasta el fin que tenía. */
    public function regresoAnticipado(int $suspensionId): void
    {
        try {
            $this->authorize(Permisos::PLANILLA_SUSPENSION_GESTIONAR);
            $r = app(PlanillaSuspensionDiarioProceso::class)->regresoAnticipado($suspensionId, $this->fecha);
            $this->obtenerResumenDiarioPlanilla();
            $this->obtenerHandsonTableReporteDiario();
            $this->cargarAusencias();
            $this->alert('success', "Listo: se quitaron {$r['revertidos']} día(s) del registro diario. Registra lo que hizo hoy.");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
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
            $this->errorAlert($th);
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
            $this->cargarAusencias();
            $this->hasUnsavedChanges = false;
            $this->modifiedRowIndexes = [];
            $this->alert('success',"Registros Guardados Correctamente.");

        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render()
    {
        return view('livewire.planilla.registro-diario.gestion-planilla-registro-diario-detalle');
    }
}