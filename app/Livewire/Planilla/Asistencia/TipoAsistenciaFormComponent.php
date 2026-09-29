<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Services\Planilla\Asistencia\PlanTipoAsistenciaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;

class TipoAsistenciaFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $codigo, $codigoOriginal, $descripcion, $horasJornal, $color, $tipoAsistenciaId;
    public $acumula_asistencia;
    public $criterio_bono_asistencia;
    public $planTipoSuspensionId;
    public $rol; // función en el sistema (PlanTipoAsistencia::ROLES)
    public $opcionesTipoSuspension = [];

    protected $listeners = ['nuevoTipoAsistencia', 'editarTipoAsistencia'];

    protected function rules()
    {
        return [
            'codigo' => 'required|string|max:10|unique:plan_tipo_asistencias,codigo,' . ($this->tipoAsistenciaId ?? 'NULL'),
            'descripcion' => 'required|string|max:255',
            'horasJornal' => 'required|numeric|min:0',
            'planTipoSuspensionId' => 'nullable|exists:plan_tipos_suspension,id',
            'rol' => 'nullable|in:' . implode(',', array_keys(\App\Models\PlanTipoAsistencia::ROLES)),
        ];
    }

    public function mount(PlanTipoAsistenciaServicio $servicio)
    {
        $this->opcionesTipoSuspension = $servicio->obtenerOpcionesTipoSuspension();
    }

    public function guardarPlanTipoAsistencia(PlanTipoAsistenciaServicio $servicio)
    {
        $this->validate();

        try {
            $datos = [
                'codigo' => $this->codigo,
                'descripcion' => $this->descripcion,
                'horas_jornal' => $this->horasJornal,
                'color' => $this->color,
                'acumula_asistencia' => (bool) $this->acumula_asistencia,
                'criterio_bono_asistencia' => $this->criterio_bono_asistencia ?? 'no_afecta',
                'plan_tipo_suspension_id' => $this->planTipoSuspensionId ?: null,
                'rol' => $this->rol ?: null,
            ];

            $servicio->guardar($datos, $this->tipoAsistenciaId);

            $mensaje = $this->tipoAsistenciaId ? '¡Actualizado con éxito!' : '¡Creado con éxito!';
            $this->alert('success', $mensaje);

            $this->dispatch('nuevoRegistro');
            $this->mostrarFormulario = false;
            $this->resetForm();
        } catch (Exception $e) {
            $this->alert('error', 'Hubo un error al guardar: ' . $e->getMessage());
        }
    }

    public function editarTipoAsistencia($tipoAsistenciaId, PlanTipoAsistenciaServicio $servicio)
    {
        try {
            $this->resetForm();
            $tipoAsistencia = $servicio->obtenerPorId($tipoAsistenciaId);

            $this->tipoAsistenciaId = $tipoAsistencia->id;
            $this->codigo = $tipoAsistencia->codigo;
            $this->codigoOriginal = $tipoAsistencia->codigo;
            $this->descripcion = $tipoAsistencia->descripcion;
            $this->horasJornal = $tipoAsistencia->horas_jornal;
            $this->color = $tipoAsistencia->color;
            $this->acumula_asistencia = $tipoAsistencia->acumula_asistencia;
            $this->criterio_bono_asistencia = $tipoAsistencia->criterio_bono_asistencia;
            $this->planTipoSuspensionId = $tipoAsistencia->plan_tipo_suspension_id;
            $this->rol = $tipoAsistencia->rol;
            $this->mostrarFormulario = true;
        } catch (Exception $e) {
            $this->alert('error', 'No se pudo cargar el registro');
        }
    }

    public function nuevoTipoAsistencia()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['codigo', 'descripcion', 'tipoAsistenciaId', 'codigoOriginal', 'acumula_asistencia','criterio_bono_asistencia', 'planTipoSuspensionId', 'rol']);
        $this->horasJornal = 0;
        $this->color = '#ffffff';
    }

    public function render()
    {
        return view('livewire.planilla.asistencia.tipo-asistencia-form-component');
    }
}