<?php

namespace App\Livewire\Planilla;

use App\Services\Planilla\Plame\PlanillaPlameConsulta;
use Livewire\Component;

/**
 * Ficha "Ver PLAME" de un trabajador: los datos del PLAME del mes en vivo, con la estructura de la boleta R08
 * de SUNAT, más costos, vacaciones y verificaciones de coherencia. No genera PDF ni Excel.
 * Se abre con el evento `verPlame` (personalId = plan_mensual_personals.id).
 */
class PlanillaPlameFichaComponent extends Component
{
    public bool $mostrar = false;
    public ?int $personalId = null;

    protected $listeners = ['verPlame'];

    public function verPlame(int $personalId): void
    {
        $this->personalId = $personalId;
        $this->mostrar = true;
    }

    public function render()
    {
        // Se arma en cada render (datos vivos): no se guarda en una propiedad pública
        $ficha = $this->mostrar && $this->personalId
            ? app(PlanillaPlameConsulta::class)->ficha($this->personalId)
            : null;

        return view('livewire.planilla.planilla-plame-ficha-component', compact('ficha'));
    }
}
