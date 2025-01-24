<?php

namespace App\Livewire;

use App\Models\LaborValoracion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class LaboresValoracionComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $valoracionId;
    public $laborId;
    public $kg_8;
    public $vigencia_desde;
    public $valor_kg_adicional;
    public $valoraciones = [];
    protected $listeners = ['listarValoracionLabor', 'confirmarEliminarValoracion'];
    public function listarValoracionLabor($laborId)
    {
        $this->resetear();

        $this->laborId = $laborId;
        $this->listarValoracion();
        $this->mostrarFormulario = true;
    }
    public function listarValoracion()
    {
        if (!$this->laborId) {
            return;
        }
        $this->valoraciones = LaborValoracion::where('labor_id', $this->laborId)->orderBy('vigencia_desde', 'desc')->get();
    }
    public function agregarValoracion()
    {

        $this->validate([
            'vigencia_desde' => 'required|date',
            'kg_8' => 'required|numeric|min:0', // Debe ser un número mayor o igual a 0
            'valor_kg_adicional' => 'required|numeric|min:0', // Debe ser un número mayor o igual a 0
        ], [
            'vigencia_desde.required' => 'Debe colocar una fecha.',
            'vigencia_desde.date' => 'La fecha no es válida.',
            'kg_8.required' => 'El campo "Unidades en 8 horas" es obligatorio.',
            'kg_8.numeric' => 'El campo "Unidades en 8 horas" debe ser un número.',
            'kg_8.min' => 'El campo "Kg en 8 horas" debe ser mayor o igual a 0.',
            'valor_kg_adicional.required' => 'El campo "Valor adicional" es obligatorio.',
            'valor_kg_adicional.numeric' => 'El campo "Valor adicional" debe ser un número.',
            'valor_kg_adicional.min' => 'El campo "Valor adicional" debe ser mayor o igual a 0.',
        ]);

        try {
            $data = [
                'labor_id' => $this->laborId,
                'kg_8' => $this->kg_8,
                'valor_kg_adicional' => $this->valor_kg_adicional,
                'vigencia_desde' => $this->vigencia_desde,
            ];
            if ($this->valoracionId) {
                $valoracionEditar = LaborValoracion::find($this->valoracionId);
                if ($valoracionEditar) {
                    $valoracionEditar->update($data);
                }
                $this->alert('success', 'Valoración modificada correctamente.');
            } else {
                LaborValoracion::create($data);
                $this->alert('success', 'Valoración registrada correctamente.');
            }
            $this->dispatch('valoracionTrabajada');
            $this->listarValoracion();
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar la Valoración de la Labor.');
        } finally {
            $this->resetear();
        }
    }
    public function resetear()
    {
        $this->reset(['vigencia_desde', 'kg_8', 'valor_kg_adicional', 'valoracionId']);
        $this->resetErrorBag();
    }
    public function editarValoracion($registroId)
    {
        $valoracion = LaborValoracion::find($registroId);
        if ($valoracion) {
            $this->valoracionId = $registroId;
            $this->vigencia_desde = $valoracion->vigencia_desde;
            $this->kg_8 = $valoracion->kg_8;
            $this->valor_kg_adicional = $valoracion->valor_kg_adicional;
        }
    }
    public function preguntarEliminar($registroId)
    {
        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminarValoracion',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'registroId' => $registroId,
            ],
        ]);
    }
    public function confirmarEliminarValoracion($data)
    {
        try {
            $registroId = $data['registroId'];
            LaborValoracion::find($registroId)->delete();
            $this->listarValoracion();
            $this->alert('success', 'Registro Eliminado Correctamente.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al eliminar la Valoración de la Labor.');
        }
    }
    public function cancelarEdicionValoracion(){
        $this->resetear();
    }
    public function render()
    {
        return view('livewire.labores-valoracion-component');
    }
}
