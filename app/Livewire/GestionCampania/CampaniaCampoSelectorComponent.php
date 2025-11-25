<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampaniaCampoSelectorComponent extends Component
{
    use LivewireAlert;
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $campanias = [];
    public $campania;
    protected $listeners = [
        'campaniaInsertada' => 'relistarNuevaCampania',
    ];
    public function updatedCampoSeleccionado($campo): void
    {
        $this->listarCampanias($campo);
    }
    public function updatedCampaniaSeleccionada($campaniaId): void
    {
        $this->campania = CampoCampania::find($campaniaId);
        if (!$this->campania) {
            $this->campaniaSeleccionada = null;
        }
    }
    public function relistarNuevaCampania(array $datos): void
    {
        $campo = $datos['campo'] ?? null;

        if ($campo) {
            $this->listarCampanias($campo);
        }
    }
    private function listarCampanias(?string $campo): void
    {
        if (empty($campo)) {
            $this->campanias = [];
            return;
        }

        $this->campanias = CampoCampania::query()
            ->where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->pluck('nombre_campania', 'id')
            ->toArray();

        $this->campaniaSeleccionada = $this->campanias ? array_key_first($this->campanias) : null;
    }
    public function eliminarCampania($campaniaSeleccionada)
    {
        try {
            
            app(CampaniaServicio::class)->eliminarCampania($campaniaSeleccionada);
            $this->campoSeleccionado = null;
            $this->listarCampanias('');

            $this->alert('success', 'Campaña Eliminada Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-campania.campania-x-campo-selector');
    }
}
