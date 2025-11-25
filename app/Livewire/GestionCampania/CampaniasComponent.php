<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CampaniasComponent extends Component
{
    use WithPagination, WithoutUrlPagination, LivewireAlert;
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $campanias = [];
    protected $listeners = ['campaniaInsertada' => 'refrescar'];
    public function updatedCampoSeleccionado($campo)
    {
        $this->resetPage();
        $this->listarCampanias($campo);
    }
    public function updatedcampaniaSeleccionada()
    {
        $this->resetPage();
    }
    public function refrescar()
    {
        $this->resetPage();
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
            ->pluck('nombre_campania', 'nombre_campania')
            ->toArray();

        $this->campaniaSeleccionada = $this->campanias ? array_key_first($this->campanias) : null;
    }
    public function eliminarCampania($campaniaSeleccionada)
    {
        try {
            app(CampaniaServicio::class)->eliminarCampania($campaniaSeleccionada);
            $this->alert('success', 'Campaña Eliminada Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function descargarReporteCampania()
    {
        try {
            $campo = $this->campoSeleccionado;
            $campania = $this->campaniaSeleccionada;

            $query = CampoCampania::query();

            // Filtrar por campo si está seleccionado
            if ($campo) {
                $query->where('campo', $campo);
            }

            // Filtrar por campaña si está seleccionada
            if ($campania) {
                $query->where('nombre_campania', $campania);
            }

            // Obtener registros ordenados
            $registros = $query->orderBy('nombre_campania', 'desc')
                ->orderBy('campo', 'asc')
                ->get();

            return app(CampaniaServicio::class)
                ->descargarReporteCampania($registros, $campo, $campania);

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        $query = CampoCampania::query();
        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }
        if ($this->campaniaSeleccionada) {
            $query->where('nombre_campania',$this->campaniaSeleccionada);
        }
        $campanias = $query->orderBy('nombre_campania', 'desc')
            ->orderBy('campo')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(20);

        return view('livewire.gestion-campania.campanias-component', [
            'campaniasGenerales' => $campanias
        ]);
    }
}
