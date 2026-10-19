<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Campania\Historia\CampaniaHistoriaConsulta;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "Historia de la campaña": la campaña contada en palabras, en orden (labores, infestación, cosecha, riego…).
 * Se abre con el evento `verHistoriaCampania` (campaniaId) desde Campañas y Campañas por campo.
 */
class CampaniaHistoriaComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $mostrar = false;
    public ?int $campaniaId = null;
    /** Tipos de evento ocultos (filtro de la línea de tiempo). */
    public array $ocultos = [];

    #[On('verHistoriaCampania')]
    public function abrir(int $campaniaId): void
    {
        $this->campaniaId = $campaniaId;
        $this->ocultos = [];
        $this->mostrar = true;
    }

    public function alternar(string $tipo): void
    {
        $this->ocultos = in_array($tipo, $this->ocultos, true)
            ? array_values(array_diff($this->ocultos, [$tipo]))
            : [...$this->ocultos, $tipo];
    }

    public function render()
    {
        $campania = null;
        $historia = null;
        if ($this->mostrar && $this->campaniaId) {
            try {
                $campania = CampoCampania::find($this->campaniaId);
                $historia = $campania ? app(CampaniaHistoriaConsulta::class)->construir($campania) : null;
            } catch (\Throwable $e) {
                $this->errorAlert($e);
            }
        }
        return view('livewire.campania.campania-historia-component', compact('campania', 'historia'));
    }
}
