<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class CampaniaCampoSelectorComponent extends Component
{
    use LivewireAlert;

    public $campoSeleccionado;
    public $campaniaSeleccionada; // El ID
    public $campanias = [];
    public $campania; // El Objeto Model

    protected $listeners = ['campaniaInsertada' => 'relistarNuevaCampania'];

    public function mount($campaniaId = null)
    {
        // 1. Determinar el campo inicial
        if ($campaniaId) {
            $campaniaModel = CampoCampania::find($campaniaId);
            if ($campaniaModel) {
                $this->campoSeleccionado = $campaniaModel->campo;
                Session::put('campo', $this->campoSeleccionado);
            }
        } else {
            $this->campoSeleccionado = Session::get('campo');
        }

        // 2. Cargar lista y seleccionar campaña
        if ($this->campoSeleccionado) {
            $this->cargarYSeleccionar($this->campoSeleccionado, $campaniaId ?: Session::get('campania'));
        }
    }

    /**
     * Centraliza la carga de la lista y la selección de la campaña activa
     */
    private function cargarYSeleccionar($campo, $campaniaIdDeseada = null)
    {
        $this->campoSeleccionado = $campo;
        
        // Cargar lista de campañas
        $this->campanias = CampoCampania::where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->pluck('nombre_campania', 'id')
            ->toArray();

        if (empty($this->campanias)) {
            $this->resetearSeleccion();
            return;
        }

        // Determinar ID a seleccionar: 1. El pedido, 2. El de sesión, 3. El primero de la lista
        $idFinal = $campaniaIdDeseada;
        if (!$idFinal || !array_key_exists($idFinal, $this->campanias)) {
            $idFinal = array_key_first($this->campanias);
        }

        $this->setCampaniaActiva($idFinal);
    }

    /**
     * Esta es la ÚNICA función que debe cambiar el estado de la campaña
     */
    private function setCampaniaActiva($id)
    {
        $this->campaniaSeleccionada = $id;
        $this->campania = CampoCampania::find($id);
        
        if ($this->campania) {
            Session::put('campania', $id);
            $this->dispatch('campania-cambiada', id: $id);
        } else {
            $this->resetearSeleccion();
        }
    }

    private function resetearSeleccion()
    {
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        Session::forget('campania');
    }

    // --- Eventos de UI ---

    public function updatedCampoSeleccionado($campo)
    {
        Session::put('campo', $campo);
        $this->cargarYSeleccionar($campo);
    }

    public function updatedCampaniaSeleccionada($id)
    {
        $this->setCampaniaActiva($id);
    }

    public function relistarNuevaCampania(array $datos)
    {
        $this->cargarYSeleccionar($datos['campo'] ?? null, $datos['id'] ?? null);
    }

    // --- Acciones ---

    public function generarBdd($campaniaId)
    {
        try {
            app(CampaniaServicio::class)->generarBddMensual($campaniaId);
            $this->alert('success', 'Datos generados correctamente.');
            // Refrescar el objeto por si cambió la ruta del archivo
            $this->campania = CampoCampania::find($campaniaId);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function eliminarCampania($id)
    {
        try {
            app(CampaniaServicio::class)->eliminarCampania($id);
            $this->cargarYSeleccionar($this->campoSeleccionado);
            $this->alert('success', 'Campaña Eliminada.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-campania.campania-x-campo-selector');
    }
}