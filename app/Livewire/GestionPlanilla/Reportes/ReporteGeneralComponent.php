<?php

namespace App\Livewire\GestionPlanilla\Reportes;
use App\Models\PlanGrupo;
use App\Services\Modulos\Planilla\GestionPlanillaResumenGeneral;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReporteGeneralComponent extends Component
{
    use LivewireAlert;
    public $filtroNombres = '';
    public $fechaInicio;
    public $fechaFin;
    public $grupoSeleccionado = '';
    public $registros = [];
    public $grupos = [];
    public function mount()
    {
        $this->grupos = PlanGrupo::all();
        $this->buscarRegistros();
    }
    public function updatedGrupoSeleccionado()
    {
        $this->buscarRegistros();
    }
    public function updatedFechaInicio()
    {
        $this->buscarRegistros();
    }
    public function updatedFechaFin()
    {
        $this->buscarRegistros();
    }
    public function buscarRegistros()
    {
        $this->registros = app(GestionPlanillaResumenGeneral::class)
            ->obtenerDataResumen($this->fechaInicio, $this->fechaFin, $this->grupoSeleccionado, $this->filtroNombres);
    }

    public function generarInformeGeneralPlanilla()
    {
        try {

            return app(GestionPlanillaResumenGeneral::class)->descargarInforme(
                $this->registros,
                $this->fechaInicio,
                $this->fechaFin,
                $this->grupoSeleccionado,
                $this->filtroNombres
            );

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.reportes.reporte-general-component');
    }
}