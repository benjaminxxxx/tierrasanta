<?php

namespace App\Livewire\Evaluaciones;

use App\Services\Produccion\MateriaPrima\PoblacionPlantaServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class EvaluacionPoblacionPlantasComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $campaniaFiltrada;
    public $evaluadorFiltro;
    public $fechaFiltro;
    public $campaniasParaFiltro = [];

    protected $listeners = ['poblacionPlantasRegistrado' => '$refresh', 'confirmarEliminarPoblacionPlanta'];

    public function mount($campaniaId = null)
    {
        $this->campaniaFiltrada = $campaniaId;
    }

    public function updatedCampoFiltrado()
    {
        $this->resetPage();
        $this->campaniasParaFiltro = [];
        $this->campaniaFiltrada = null;

        if (!$this->campoFiltrado) {
            return;
        }

        $this->campaniasParaFiltro = app(CampaniaServicio::class)->buscarCampaniasPorCampo($this->campoFiltrado);
        if ($this->campaniasParaFiltro->isNotEmpty()) {
            $this->campaniaFiltrada = $this->campaniasParaFiltro->first()->id;
        }
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
    public function confirmarEliminarPoblacionPlanta($data): void
    {
        try {
            app(PoblacionPlantaServicio::class)->eliminar($data['poblacionId']);
            $this->alert('success', 'Registro eliminado correctamente.');
            $this->dispatch('poblacionPlantasEliminado');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function exportarReporte(){
        try {
            $data = [
                'campo' => $this->campoFiltrado,
                'campania' => $this->campaniaFiltrada,
                'evaluador' => $this->evaluadorFiltro,
                'fecha' => $this->fechaFiltro
            ];
            return app(PoblacionPlantaServicio::class)->exportar($data);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $poblacionPlantas = PoblacionPlantaServicio::buscar([
            'campo' => $this->campoFiltrado,
            'campania_id' => $this->campaniaFiltrada,
            'evaluador' => $this->evaluadorFiltro,
            'fecha' => $this->fechaFiltro,
        ]);

        return view('livewire.evaluaciones.evaluacion-poblacion-plantas-component', [
            'poblacionPlantas' => $poblacionPlantas
        ]);
    }
}
