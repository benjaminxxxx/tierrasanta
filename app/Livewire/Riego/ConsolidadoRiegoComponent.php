<?php

namespace App\Livewire\Riego;

use App\Livewire\Traits\ConFechaReporteDia;
use App\Models\ConsolidadoRiego;
use Date;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsolidadoRiegoComponent extends Component
{
    use LivewireAlert, ConFechaReporteDia, \App\Traits\HandlesAlerts;
    public $consolidado_riegos = [];
    protected $listeners = ['RefrescarMapa' => '$refresh', 'registroConsolidado' => '$refresh'];
    public function mount()
    {
        $this->inicializarFecha();
    }
    protected function despuesFechaModificada($fecha)
    {

    }
    public function consolidarRegistro($documento)
    {
        if (!$documento || !$this->fecha) {
            return;
        }
        try {
            $this->dispatch('consolidarRegador', $documento, $this->fecha);
            $this->alert("success", "Registro consolidado");

        } catch (\Throwable $th) {
            $this->errorAlert("Ocurrió un error al consolidar el registro");
        }
    }
    public function render()
    {
        // Sin fecha, lista vacía (antes quedaba en null y la vista fallaba en el foreach)
        $this->consolidado_riegos = $this->fecha
            ? ConsolidadoRiego::with(['trabajador'])->whereDate('fecha', $this->fecha)->get()
            : collect();
        return view('livewire.riego.consolidado-riego-component');
    }
}
