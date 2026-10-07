<?php

namespace App\Livewire\Evaluacion;

use App\Services\Evaluacion\BrotesPorPisoServicio;
use App\Services\Campania\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class EvaluacionBrotesComponent extends Component
{
    use WithPagination, WithoutUrlPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $campaniaId;
    public $campaniaFiltrada;
    public $fechaDesde;
    public $fechaHasta;
    public $fechaFiltro;
    public $evaluadorFiltro;
    public $campaniasParaFiltro = [];
    protected $listeners = ['brotesPorPisoRegistrado' => '$refresh','confirmareliminarBrotesXPiso'];

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
    public function eliminarBrotesXPiso($brotesId)
    {
        $e = \App\Models\EvalBrotesPorPiso::find($brotesId);
        $this->confirm('¿Eliminar la evaluación del ' . $e?->fecha?->format('d/m/Y') . '? Es la última de la campaña; queda registrada en la auditoría.', [
            'onConfirmed' => 'confirmareliminarBrotesXPiso',
            'data' => [
                'evaluacionBrotesXPisoId' => $brotesId,
            ],
        ]);
    }
    public function confirmareliminarBrotesXPiso($data)
    {
        try {
            app(BrotesPorPisoServicio::class)->eliminar($data['evaluacionBrotesXPisoId']);
            $this->alert('success', 'Evaluación eliminada (queda en la auditoría).');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function exportarReporteBrotesXPiso(){
        try {
            $data = [
                'campo' => $this->campoFiltrado,
                'campania' => $this->campaniaFiltrada,
                'evaluador' => $this->evaluadorFiltro,
                'fecha' => $this->fechaFiltro
            ];
            return app(BrotesPorPisoServicio::class)->exportar($data);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {

        $evaluaciones = BrotesPorPisoServicio::buscar([
            'campo' => $this->campoFiltrado,
            'campania_id' => $this->campaniaFiltrada,
            'evaluador' => $this->evaluadorFiltro,
            'fecha' => $this->fechaFiltro,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
        ]);

        return view('livewire.evaluacion.evaluacion-brotes-component', [
            'evaluacionesBrotes' => $evaluaciones
        ]);
    }
}
