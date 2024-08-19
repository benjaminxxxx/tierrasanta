<?php

namespace App\Livewire;

use App\Models\AsignacionFamiliar;
use App\Models\Empleado;
use Livewire\Component;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AsignacionFamiliarFormComponent extends Component
{
    use LivewireAlert;
    public $isFormOpen = false;
    public $nombre_empleado;
    public $empleadoId;
    public $nombres;
    public $fecha_nacimiento;
    public $documento;
    public $esta_estudiando;
    public $asignaciones_familiares;
    public $asignacionId;
    protected $listeners = ['AgregarAsignacionFamiliar','confirmarEliminarHijo'];
    protected function rules()
    {
        return [
            'nombres' => 'required|string',
            'fecha_nacimiento'=>'required|date',
            'documento' => [
                'required',
                'string',
                Rule::unique('asignacion_familiar', 'documento')->ignore($this->empleadoId),
            ],
        ];
    }

    protected $messages = [
        'nombres.required' => 'El Nombre es obligatorio.',
        'documento.required' => 'El Documento es obligatorio.',
        'documento.unique' => 'El Documento ya está en uso.',
        'fecha_nacimiento.required' => 'La fecha de Nacimiento es obligatorio.',
        'fecha_nacimiento.date' => 'La fecha de Nacimiento no es Válida.',
    ];

    public function render()
    {
        $this->asignaciones_familiares = AsignacionFamiliar::where('empleado_id',$this->empleadoId)->get();
        return view('livewire.asignacion-familiar-form-component');
    }
    public function AgregarAsignacionFamiliar($code){
        $this->resetErrorBag();
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $this->empleadoId = $empleado->id;
            $this->nombre_empleado = $empleado->nombreCompleto;
            $this->isFormOpen = true;
        }else{
            $this->alert('error','El empleado ya no existe');
        }
    }
    public function closeForm(){
        $this->isFormOpen = false;
    }
    public function store()
    {
        Log::info('Store function called');
        $this->validate();
        try {
            $data = [
                'nombres' => mb_strtoupper($this->nombres),
                'empleado_id' => $this->empleadoId,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'documento' => $this->documento,
                'esta_estudiando' => $this->esta_estudiando ?? 0,
            ];

            AsignacionFamiliar::create($data);
            $this->dispatch("HijoRegistrado");
            $this->alert('success', 'Registro actualizado exitosamente.');
            $this->reset(['nombres', 'esta_estudiando', 'fecha_nacimiento', 'documento']);
            
            $this->resetErrorBag();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function confirmarEliminacion($id)
    {
        $this->asignacionId = $id;

        $this->alert('question', '¿Está seguro(a) que desea eliminar el Hijo del Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminarHijo',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function confirmarEliminarHijo()
    {
        if ($this->asignacionId) {
            $asignacion = AsignacionFamiliar::find($this->asignacionId);
            if ($asignacion) {
                $asignacion->delete();
                $this->dispatch("HijoRegistrado");
            }
        }
    }
}
