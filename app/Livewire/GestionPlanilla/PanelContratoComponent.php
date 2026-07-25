<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanEmpleado;
use App\Services\Planilla\ContratoStatsService;
use Livewire\Component;

class PanelContratoComponent extends Component
{
    public $breadcrumb = [];
    public $recentContracts;
    public $statsActive;
    public $statsTrial;
    public $statsExpiring;
    public $statsTerminated;
    public $personalSinContratos = [];
    public $mostrarModalSinContratos = false;

    public function mount(ContratoStatsService $statsService)
    {
        $this->breadcrumb = [
            ['label' => 'Panel contratos']
        ];
        // Parámetros duros por el momento (ejemplo: Julio 2026 o nulos para el mes actual)
        $mes = null;  // ej: 7
        $anio = null; // ej: 2026

        // Extrae directamente $statsActive, $statsTrial, $statsExpiring, $statsTerminated
        $stats = $statsService->getStats($mes, $anio);
        $this->recentContracts = $statsService->getRecentContracts(5);
        $this->statsActive = $stats['statsActive'];
        $this->statsTrial = $stats['statsTrial'];
        $this->statsExpiring = $stats['statsExpiring'];
        $this->statsTerminated = $stats['statsTerminated'];
        $this->personalSinContratos = PlanEmpleado::doesntHave('contratos')->get();
    }
    public function navigate($go)
    {
        $this->redirect(route('planilla.contratos'));
    }
    public function mostrarListaSinContratos()
    {
        $this->mostrarModalSinContratos = true;
    }
    public function render()
    {
        return view('livewire.gestion-planilla.panel-contrato-component');
    }
}
