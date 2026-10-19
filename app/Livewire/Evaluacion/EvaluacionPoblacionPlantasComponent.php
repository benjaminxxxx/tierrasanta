<?php

namespace App\Livewire\Evaluacion;

use App\Services\Evaluacion\PoblacionPlantaServicio;
use App\Services\Campania\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class EvaluacionPoblacionPlantasComponent extends Component
{
    use WithPagination;
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $campoFiltrado;
    public $campaniaFiltrada;
    public $evaluadorFiltro;
    public $fechaFiltro;
    public $campaniasParaFiltro = [];

    protected $listeners = ['poblacionPlantasRegistrado' => '$refresh', 'confirmarEliminarPoblacionPlanta'];

    public ?int $evaluacionResaltada = null;

    public function mount($campaniaId = null)
    {
        // Enlace desde el informe de campaña: ?campania=ID (o el id de la ruta) y ?evaluacion=ID para resaltarla
        $campaniaId = $campaniaId ?: request()->query('campania');
        $this->evaluacionResaltada = (int) request()->query('evaluacion') ?: null;
        if ($campaniaId && ($campania = \App\Models\CampoCampania::find($campaniaId))) {
            $this->campoFiltrado = $campania->campo;
            $this->campaniasParaFiltro = app(CampaniaServicio::class)->buscarCampaniasPorCampo($campania->campo);
        }
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
            $this->errorAlert($th);
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
            $this->errorAlert($th);
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

        return view('livewire.evaluacion.evaluacion-poblacion-plantas-component', [
            'poblacionPlantas' => $poblacionPlantas
        ]);
    }
}
