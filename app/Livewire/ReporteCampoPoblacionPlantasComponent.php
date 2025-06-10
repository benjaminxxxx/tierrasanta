<?php

namespace App\Livewire;

use App\Services\CampaniaServicio;
use App\Services\PoblacionPlantaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCampoPoblacionPlantasComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $detalleComponentId;
    public $campaniaUnica;
    public $campaniaId;

    protected $listeners = ['poblacionPlantasRegistrado' => '$refresh', 'confirmarEliminarPoblacionPlanta'];

    public function mount($campaniaId = null, $campaniaUnica = false)
    {
        $this->campaniaId = $campaniaId;
        $this->campaniaUnica = $campaniaUnica;
    }
    public function verDetallePoblacion($poblacionPlantaId)
    {
        $this->detalleComponentId = $poblacionPlantaId;
    }

    public function updatedCampoFiltrado()
    {
        $this->resetPage();
    }
    public function eliminarPoblacionPlanta($poblacionId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarPoblacionPlanta',
            'data' => [
                'poblacionId' => $poblacionId,
            ],
        ]);
    }
    public function enviarHistorialPoblacionPlantas($campaniaId)
    {
        try {
            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->registrarHistorialPoblacionPlantas();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function confirmarEliminarPoblacionPlanta($data): void
    {
        try {
            $campaniaId = PoblacionPlantaServicio::eliminar($data['poblacionId']);
            $this->enviarHistorialPoblacionPlantas($campaniaId);
            $this->alert('success', 'Registro eliminado correctamente.');
            $this->dispatch('poblacionPlantasEliminado');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->alert('warning', 'El registro ya no existe.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado.');
        }
    }
    public function render()
    {
        $poblacionPlantas = PoblacionPlantaServicio::listarConFiltros([
            'campo' => $this->campoFiltrado,
            'campania_id' => $this->campaniaUnica ? $this->campaniaId : null,
        ]);

        return view('livewire.reporte-campo-poblacion-plantas-component', [
            'poblacionPlantas' => $poblacionPlantas
        ]);
    }
}
