<?php

namespace App\Livewire;

use App\Models\TipoAsistencia;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TipoAsistenciaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $codigo;
    public $codigoOriginal;
    public $descripcion;
    public $horasJornal;
    public $color;
    public $tipoAsistenciaId;
    protected $listeners = ['nuevoTipoAsistencia','editarTipoAsistencia'];
     
    public function storeTipoAsistencia()
    {

        // Definir las reglas de validación dinámicamente
        $rules = [
            'codigo' => 'required|string|max:10|unique:tipo_asistencias,codigo,' . ($this->tipoAsistenciaId ?? 'NULL'),
            'descripcion' => 'required|string|max:255',
            'horasJornal' => 'required|numeric|min:0',
        ];
        $messages = [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.string' => 'El código debe ser una cadena de texto.',
            'codigo.max' => 'El código no puede tener más de 10 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',
            'horasJornal.required' => 'El campo horas jornal es obligatorio.',
            'horasJornal.numeric' => 'El campo horas jornal debe ser un número.',
            'horasJornal.min' => 'El campo horas jornal debe ser al menos 0.',
        ];

        // Realizar la validación
        $this->validate($rules,$messages);

        // Preparar los datos para insertar o actualizar
        $data = [
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'horas_jornal' => $this->horasJornal,
            'color' => $this->color,
        ];

        try {
            if ($this->tipoAsistenciaId) {
                // Si hay un ID, se realiza un update
                $tipoAsistencia = TipoAsistencia::findOrFail($this->tipoAsistenciaId);
                $tipoAsistencia->update($data);
                $this->alert('success', '¡Tipo de asistencia actualizado con éxito!');
                
            } else {
                TipoAsistencia::create($data);
                $this->alert('success', '¡Tipo de asistencia creado con éxito!');
            }

            $this->dispatch("nuevoRegistro");
            $this->mostrarFormulario = false;
            $this->resetForm();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Hubo un error al guardar: ' . $e->getMessage());
        }
    }
    public function editarTipoAsistencia($tipoAsistenciaId)
    {
        $this->resetForm();

        $this->tipoAsistenciaId = $tipoAsistenciaId;
        $tipoAsistencia = TipoAsistencia::findOrFail($this->tipoAsistenciaId);

        if($tipoAsistencia){
            $this->codigo = $tipoAsistencia->codigo;
            $this->codigoOriginal = $tipoAsistencia->codigo; //pasa que en el form si es codigo=a se volvera readonly, y no me dejara modificar, si estoy editando otro y cambio a A, no debria bloquearse
            $this->descripcion = $tipoAsistencia->descripcion;
            $this->horasJornal = $tipoAsistencia->horas_jornal;
            $this->color = $tipoAsistencia->color;
            $this->mostrarFormulario = true;   
        }
        
    }
    public function nuevoTipoAsistencia(){
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function resetForm(){
        $this->resetErrorBag();
        $this->codigo = null;
        $this->descripcion = null;
        $this->horasJornal = 0;
        $this->color = '#ffffff';
        $this->tipoAsistenciaId = null;
    }
    public function render()
    {
        return view('livewire.tipo-asistencia-form-component');
    }
}
