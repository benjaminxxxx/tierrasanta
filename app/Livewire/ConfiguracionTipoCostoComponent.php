<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\ContabilidadCostoTipo;
use Illuminate\Validation\Rule;

class ConfiguracionTipoCostoComponent extends Component
{
    use LivewireAlert;
    public $contabilidadCostoTipoId, $tipoCosto, $nombreCosto;
    public $contabilidadCostoTipos;
    protected $listeners = ['confirmarEliminarContabilidadCostoTipo'];
    public function mount()
    {
        $this->resetForm();
    }
    public function agregarNuevaDescripcion()
    {
        $this->validate([
            'tipoCosto' => 'required|string|max:255',
            'nombreCosto' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contabilidad_costo_tipos', 'nombre_costo')->ignore($this->contabilidadCostoTipoId)
            ],
        ], [
            'tipoCosto.required' => 'El tipo de costo es obligatorio.',
            'tipoCosto.string' => 'El tipo de costo debe ser un texto válido.',
            'tipoCosto.max' => 'El tipo de costo no puede superar los 255 caracteres.',

            'nombreCosto.required' => 'La descripción es obligatoria.',
            'nombreCosto.string' => 'La descripción debe ser un texto válido.',
            'nombreCosto.max' => 'La descripción no puede superar los 255 caracteres.',
            'nombreCosto.unique' => 'Esta descripción ya está registrada.',
        ]);
        try {
            $data = [
                'tipo_costo' => $this->tipoCosto,
                'nombre_costo' => mb_strtoupper($this->nombreCosto),
            ];

            if ($this->contabilidadCostoTipoId) {
                // Editar si existe el ID
                $costo = ContabilidadCostoTipo::findOrFail($this->contabilidadCostoTipoId);
                $costo->update($data);
                $this->alert('success', 'Tipo de costo actualizado correctamente.');
            } else {
                // Registrar nuevo si no hay ID
                ContabilidadCostoTipo::create($data);
                $this->alert('success', 'Nuevo tipo de costo agregado correctamente.');
            }
            $this->resetForm();

        } catch (\Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al registrar el tipo de contabilidad.');
        }
    }
    public function editarTipoCosto($contabilidadCostoTipoId)
    {
        try {
            // Resetear valores previos
            $this->resetForm();

            // Buscar el tipo de costo por ID
            $costo = ContabilidadCostoTipo::findOrFail($contabilidadCostoTipoId);

            // Asignar valores
            $this->contabilidadCostoTipoId = $costo->id;
            $this->tipoCosto = $costo->tipo_costo;
            $this->nombreCosto = $costo->nombre_costo;
        } catch (\Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'No se encontró el tipo de costo.');
        }
    }
    public function preguntarEliminarContabilidadCostoTipo($contabilidadCostoTipoId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarContabilidadCostoTipo',
            'data' => [
                'contabilidadCostoTipoId' => $contabilidadCostoTipoId,
            ],
        ]);
    }
    public function confirmarEliminarContabilidadCostoTipo($data)
    {
        try {
            $contabilidadCostoTipoId = $data['contabilidadCostoTipoId'];
    
            $costoTipo = ContabilidadCostoTipo::findOrFail($contabilidadCostoTipoId);
            $costoTipo->delete();
    
            $this->alert('success', 'El tipo de costo ha sido eliminado correctamente.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('log', 'Registro no encontrado: ' . $e->getMessage());
            $this->alert('error', 'El tipo de costo no existe o ya fue eliminado.');
        } catch (\Exception $e) {
            $this->dispatch('log', 'Error al eliminar: ' . $e->getMessage());
            $this->alert('error', 'Ocurrió un error interno al intentar eliminar el tipo de costo.');
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->tipoCosto ??= 'operativo';
        $this->reset(['contabilidadCostoTipoId', 'nombreCosto']); // Limpiar el formulario
    }
    public function render()
    {
        $this->contabilidadCostoTipos = ContabilidadCostoTipo::orderBy('tipo_costo')->orderBy('created_at')->get();
        return view('livewire.configuracion-tipo-costo-component');
    }
}
