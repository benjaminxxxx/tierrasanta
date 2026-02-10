<?php

namespace App\Livewire\GestionPlanilla;

use App\Services\Planilla\ConceptoPlanillaServicio;
use App\Services\Planilla\ImportarConceptosProceso;
use Dotenv\Exception\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConceptosPlanillaComponent extends Component
{
    use LivewireAlert;
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
            $this->alert('error', $th->getMessage());
        }catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.conceptos-planilla-component');
    }
}
