<?php

namespace App\Livewire\Planilla;

use App\Services\Planilla\ConceptoPlanillaServicio;
use App\Services\Planilla\ImportarConceptosProceso;
use Dotenv\Exception\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConceptosPlanillaComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $conceptos = [];
    public function mount(){
        $this->conceptos = ConceptoPlanillaServicio::leer();
    }
    public function guardarConceptos($data)
    {
        try {
            ImportarConceptosProceso::ejecutar($data);
            $this->alert('success', 'Conceptos de planilla sincronizados correctamente.');
        } catch (ValidationException $th) {
            $this->errorAlert($th);
        }catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render()
    {
        return view('livewire.planilla.conceptos-planilla-component');
    }
}
