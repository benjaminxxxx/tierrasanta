<?php

namespace App\Livewire\Planilla;

use App\Services\Planilla\Plame\PlanillaPlameConsulta;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Sección PLAME de /planilla/bn: tabla del PLAME del mes con buscador y "Ver PLAME" por trabajador
 * (ficha en PlanillaPlameFichaComponent).
 */
class PlanillaPlameComponent extends Component
{
    use LivewireAlert;

    public $mes;
    public $anio;

    /** Nombre o DNI. */
    public string $buscar = '';

    protected $listeners = ['planillaGnerada' => '$refresh'];

    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function verPlame(int $personalId): void
    {
        $this->dispatch('verPlame', personalId: $personalId);
    }

    public function render()
    {
        $consulta = app(PlanillaPlameConsulta::class);
        $empleados = $this->mes && $this->anio
            ? $consulta->listar((int) $this->mes, (int) $this->anio, $this->buscar)
            : collect();

        return view('livewire.planilla.planilla-plame-component', [
            'empleados' => $empleados,
            'totalMes' => $this->buscar !== '' && $this->mes && $this->anio
                ? $consulta->listar((int) $this->mes, (int) $this->anio)->count()
                : $empleados->count(),
        ]);
    }
}
