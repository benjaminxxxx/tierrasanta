<?php

namespace App\Livewire\GestionPlanilla\DerechoHabiente;

use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Carbon\Carbon;
use App\Domain\DerechoHabiente\EmpleadoAsignacionFamiliarService;

class DerechoHabienteEstadisticasModalComponent extends Component
{
    use ConSelectorMes;
    public bool $show = false;
    public array $resumen = [];

    protected $listeners = ['abrirDerechoHabienteEstadisticas' => 'abrir'];

    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargar();
    }
    public function abrir()
    {
        $this->cargar();
        $this->show = true;
    }
    private function cargar(): void
    {
        $this->resumen = app(EmpleadoAsignacionFamiliarService::class)->resumenMes($this->mes, $this->anio);
    }

    public function cerrar()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.gestion-planilla.derecho-habiente.derecho-habiente-estadisticas-modal-component');
    }
}