<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\TipoAsistencia;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;

class TipoAsistenciaComponent extends Component
{
    use LivewireAlert;
    public $codigo, $descripcion, $horasJornal, $tipoAsistencias;
    public $color;
    public $tipoAsistenciaId = null;
    protected $listeners = ["confirmarEliminar", 'updateColor' => 'setColor','resturar','rerender'=>'$refresh'];

   
    public function setColor($colorValue)
    {
        $this->color = $colorValue;
    }
  
    public function agregarTipoAsistencia()
    {
        $id = $this->tipoAsistenciaId;

        // Definir las reglas de validación dinámicamente
        $rules = [
            'codigo' => 'required|string|max:10|unique:tipo_asistencias,codigo,' . ($id ?? 'NULL'),
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
            if ($id) {
                // Si hay un ID, se realiza un update
                $tipoAsistencia = TipoAsistencia::findOrFail($id);
                $tipoAsistencia->update($data);
                $this->alert('success', '¡Tipo de asistencia actualizado con éxito!');
                
            } else {
                // Si no hay ID, se realiza un create
                //TipoAsistencia::create($data);
                //$this->alert('success', '¡Tipo de asistencia creado con éxito!');
            }

            $this->dispatch("setColorEdit");
            $this->resetInputFields();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Hubo un error al guardar: ' . $e->getMessage());
        }
    }
    public function editarTipoAsistencia($id)
    {
        $tipoAsistencia = TipoAsistencia::findOrFail($id);
        $this->tipoAsistenciaId = $tipoAsistencia->id;
        $this->codigo = $tipoAsistencia->codigo;
        $this->descripcion = $tipoAsistencia->descripcion;
        $this->horasJornal = $tipoAsistencia->horas_jornal;
        $this->color = $tipoAsistencia->color;
    }
    public function eliminarTipoAsistencia($id)
    {

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', 
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'id' => $id,
            ],
        ]);
    }

    public function confirmarEliminar($data)
    {
        try {
            $tipoAsistencia = TipoAsistencia::findOrFail($data['id']);
            $tipoAsistencia->delete();
            $this->alert('success', '¡Tipo de asistencia eliminado con éxito!');

        } catch (QueryException $e) {
            $this->alert('error', 'Hubo un error al eliminar: ' . $e->getMessage());
        }
    }

    public function resetInputFields()
    {
        $this->codigo = '';
        $this->descripcion = '';
        $this->horasJornal = '';
        $this->color = '';
        $this->tipoAsistenciaId = null;
    }
    public function preguntarRestaurar(){
        $this->alert('question', 'Está a punto de restaurar los valores por defecto, ¿desea continuar?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Resturar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'resturar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function resturar(){
        try {
            TipoAsistencia::truncate();

            Artisan::call('db:seed', [
                '--class' => 'TipoAsistenciaSeeder'
            ]);
            $this->resetInputFields();
            $this->dispatch("setColorEdit");
            $this->alert("success","Registro Restaurado con Éxito");
        } catch (\Throwable $th) {
            $this->alert("error",$th->getMessage());
        }
    }
    public function render()
    {
        $this->tipoAsistencias = TipoAsistencia::all();
        return view('livewire.tipo-asistencia-component');
    }
}
