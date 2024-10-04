<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\TipoAsistencia;
use Illuminate\Database\QueryException;

class TipoAsistenciaComponent extends Component
{
    use LivewireAlert;
    public $codigo, $descripcion, $horasJornal, $tipoAsistencias;
    public $color = '#ffffff';
    public $tipoAsistenciaId = null;


    protected $listeners = ["confirmarEliminar", 'updateColor' => 'setColor'];
    public function mount()
    {
        $this->obtenerTiposAsistencia();
    }
    public function setColor($colorValue)
    {
        $this->color = $colorValue;
    }
    public function obtenerTiposAsistencia()
    {
        $this->tipoAsistencias = TipoAsistencia::all();
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

        // Realizar la validación
        $this->validate($rules);

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
                TipoAsistencia::create($data);
                $this->alert('success', '¡Tipo de asistencia creado con éxito!');
            }

            // Resetear los campos y recargar la lista de tipos de asistencia
            $this->resetInputFields();
            $this->obtenerTiposAsistencia();

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
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
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

            $this->obtenerTiposAsistencia();
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
    public function render()
    {
        return view('livewire.tipo-asistencia-component');
    }
}
