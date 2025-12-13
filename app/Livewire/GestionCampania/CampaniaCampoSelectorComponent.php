<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

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
    public function mount()
    {
        $this->campoSeleccionado = Session::get('campo');
        if ($this->campoSeleccionado) {
            $this->listarCampanias($this->campoSeleccionado);
        }

    }
    public function updatedCampoSeleccionado($campo): void
    {
        Session::put('campo', $campo);
        $this->listarCampanias($campo);
    }
    public function updatedCampaniaSeleccionada($campaniaId): void
    {
        Session::put('campania', $campaniaId);
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
            $this->campaniaSeleccionada = null;
            return;
        }

        $this->campanias = CampoCampania::query()
            ->where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->pluck('nombre_campania', 'id')
            ->toArray();

        // Si no hay campañas disponibles
        if (empty($this->campanias)) {
            $this->campaniaSeleccionada = null;
            Session::forget('campania');
            return;
        }

        // 1. Si existe una campaña guardada en sesión
        $campaniaSesion = Session::get('campania');

        // 2. Validar si la campaña en sesión está en la lista
        if ($campaniaSesion && array_key_exists($campaniaSesion, $this->campanias)) {
            $this->campaniaSeleccionada = $campaniaSesion;
            return;
        }

        // 3. Si no existe o no es válida, seleccionar la primera campaña
        $this->campaniaSeleccionada = array_key_first($this->campanias);

        // Actualizar sesión con la primera
        Session::put('campania', $this->campaniaSeleccionada);
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
