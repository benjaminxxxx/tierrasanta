<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\ContabilidadCostoDetalle;
use App\Models\ContabilidadCostoRegistro;
use App\Models\ContabilidadCostoTipo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ContabilidadCostoFijoOperativoComponent extends Component
{
    use LivewireAlert;
    public $tipoCosto = 'operativo';
    public $tipoCostoId;
    public $campos = [];
    public $contabilidadCostoTipos = [];
    public $camposSeleccionados = [];
    public $contabilidadCostoRegistroId;
    public $fecha, $valor;
    public $contabilidadCostoRegistros = [];
    protected $listeners = ['confirmarEliminarContabilidadRegistro'];

    public function mount()
    {
        $this->actualizarNombresCostos();
        $this->campos = Campo::listar();
    }
    public function updatedTipoCosto()
    {
        $this->actualizarNombresCostos();
    }

    private function actualizarNombresCostos()
    {
        $this->contabilidadCostoTipos = ContabilidadCostoTipo::where('tipo_costo', $this->tipoCosto)->get();
    }
    public function agregarRegistroCosto()
    {
        $this->validate([
            'tipoCostoId' => 'required|exists:contabilidad_costo_tipos,id',
            'fecha' => 'required|date',
            'valor' => 'required|numeric|min:0.01',
        ], [
            'tipoCostoId.required' => 'El campo Tipo de Costo es obligatorio.',
            'tipoCostoId.exists' => 'El Tipo de Costo seleccionado no existe.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.numeric' => 'El valor debe ser un número.',
            'valor.min' => 'El valor debe ser mayor o igual a 0.01.',
        ]);

        try {
            // Guardar el registro
            $registro = ContabilidadCostoRegistro::create([
                'nombre_costo_id' => $this->tipoCostoId,
                'fecha' => $this->fecha,
                'valor' => $this->valor,
            ]);

            // Asignar los campos seleccionados al registro
            if (!empty($this->camposSeleccionados)) {
                foreach ($this->camposSeleccionados as $camposSeleccionado) {
                    ContabilidadCostoDetalle::create([
                        'registro_costo_id' => $registro->id,
                        'campo' => $camposSeleccionado,
                    ]);
                }
            }

            // Resetear el formulario después de guardar
            $this->resetForm();
            $this->alert('success', 'Registro de costo agregado correctamente.');
        } catch (\Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al agregar el registro de costo.');
        }
    }
    public function preguntarEliminarContabilidadCostoRegistro($contabilidadCostoRegistroId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarContabilidadRegistro',
            'data' => [
                'contabilidadCostoRegistroId' => $contabilidadCostoRegistroId,
            ],
        ]);
    }
    public function confirmarEliminarContabilidadRegistro($data)
    {
        try {
            $contabilidadCostoRegistroId = $data['contabilidadCostoRegistroId'];
    
            $contabilidadCostoRegistro = ContabilidadCostoRegistro::findOrFail($contabilidadCostoRegistroId);
            $contabilidadCostoRegistro->detalles->each(function($detalle) {
                $detalle->delete();
            }); //no hay relacion por eso debo eliminarlo manualmente unque necesitamos hacer la prueba de comprobacion
            $contabilidadCostoRegistro->delete();
    
            $this->alert('success', 'El registro se ha eliminado correctamente.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('log', 'Registro no encontrado: ' . $e->getMessage());
            $this->alert('error', 'El registro no existe o ya fue eliminado.');
        } catch (\Exception $e) {
            $this->dispatch('log', 'Error al eliminar: ' . $e->getMessage());
            $this->alert('error', 'Ocurrió un error interno al intentar eliminar el registro.');
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->camposSeleccionados = [];
        $this->valor = '';
    }
    public function render()
    {
        $this->contabilidadCostoRegistros = ContabilidadCostoRegistro::orderBy('fecha','desc')->get();
        return view('livewire.contabilidad-costo-fijo-operativo-component');
    }
}
