<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Models\PlanContrato;
use App\Services\Modulos\Planilla\GestionPlanillaEmpleados;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use App\Models\PlanEmpleado;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GestionPlanillaEmpleadosFormComponent extends Component
{
    use LivewireAlert, ConGrupoPlanilla;
    public $empleadoId;
    public $nombres;
    public $apellido_paterno;
    public $apellido_materno;
    public $documento;
    public $email;
    public $direccion;
    public $genero;
    public $fecha_nacimiento;
    public $fecha_ingreso;
    public $mostrarFormularioEmpleados = false;
    protected $listeners = ['editarEmpleado', 'abrirFormularioNuevoEmpleado'];
    public function editarEmpleado($id)
    {
        $this->resetForm();
        $empleado = app(GestionPlanillaEmpleados::class)->obtenerEmpleadoPorUuid($id);
        
        if ($empleado) {

            $this->empleadoId = $empleado->id;
            $this->nombres = $empleado->nombres;
            $this->apellido_paterno = $empleado->apellido_paterno;
            $this->apellido_materno = $empleado->apellido_materno;
            $this->documento = $empleado->documento;
            $this->email = $empleado->email;
            $this->numero = $empleado->numero;
            $this->direccion = $empleado->direccion;
            $this->genero = $empleado->genero;
            $this->fecha_nacimiento = $empleado->fecha_nacimiento;
            $this->fecha_ingreso = $empleado->fecha_ingreso;
            $this->comentarios = $empleado->comentarios;
            $this->orden = $empleado->orden;
            $this->mostrarFormularioEmpleados = true;
        }
    }
    public function guardarEmpleado()
    {
        try {
            $datos = [
                'nombres' => mb_strtoupper($this->nombres),
                'apellido_paterno' => mb_strtoupper($this->apellido_paterno),
                'apellido_materno' => mb_strtoupper($this->apellido_materno),
                'documento' => $this->documento,
                'email' => $this->email,
                'direccion' => $this->direccion,
                'genero' => $this->genero,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'fecha_ingreso' => $this->fecha_ingreso,
            ];

            app(GestionPlanillaEmpleados::class)->guardarEmpleado($datos,$this->empleadoId);
            $this->alert('success', 'Los datos fueron guardados correctamente');
            $this->mostrarFormularioEmpleados = false;
            $this->dispatch('empleadoGuardado');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function abrirFormularioNuevoEmpleado()
    {
        $this->resetForm();
        $this->mostrarFormularioEmpleados = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'documento',
            'email',
            'direccion',
            'genero',
            'fecha_nacimiento',
            'fecha_ingreso',
            'empleadoId'
        );
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados-form');
    }
}
