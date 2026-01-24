<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanPeriodo;
use App\Models\PlanTipoAsistencia;
use App\Services\PlanillaPeriodoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class PeriodosPlanillaComponent extends Component
{
    use LivewireAlert, WithPagination;

    // Propiedades de Estado
    public bool $mostrarFormularioPeriodo = false;
    public ?int $periodoId = null;

    // Datos del Formulario y Filtros
    public array $periodo = [];
    public array $filtros = [];
    public array $stats = [];

    protected $listeners = ['modalHasStop', 'eliminarPeriodoConfirmado'];

    public function mount()
    {
        $this->resetForm();
        $this->cargarEstadistica();
    }

    /**
     * Propiedades computadas para evitar recargar datos estáticos en cada request
     */
    public function getEmpleadosProperty()
    {
        return EmpleadoServicio::cargarSearchableEmpleadosPlanilla();
    }

    public function getTiposAsistenciaProperty()
    {
        return PlanTipoAsistencia::all();
    }

    public function cargarEstadistica(): void
    {
        $this->stats = app(PlanillaPeriodoServicio::class)->obtenerResumenPeriodosActivos();
    }

    public function guardarPeriodo(PlanillaPeriodoServicio $servicio): void
    {
        try {
            $servicio->guardarPeriodo($this->periodo, $this->periodoId);

            $this->alert('success', 'Operación realizada con éxito.');
            $this->mostrarFormularioPeriodo = false;
            $this->cargarEstadistica();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function editarPeriodo(int $id): void
    {
        $periodo = PlanPeriodo::findOrFail($id);

        $this->periodoId = $periodo->id;
        $this->periodo = [
            'plan_empleado_id' => $periodo->plan_empleado_id,
            'codigo' => $periodo->codigo,
            'fecha_inicio' => $periodo->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $periodo->fecha_fin?->format('Y-m-d'),
            'observaciones' => $periodo->observaciones,
            'motivo_modificacion' => $periodo->motivo_modificacion
        ];

        $this->mostrarFormularioPeriodo = true;
    }

    public function eliminarPeriodoConfirmado(int $id, string $motivo, PlanillaPeriodoServicio $servicio): void
    {
        try {
            $servicio->eliminarPeriodo($id, $motivo);
            $this->alert('success', 'Registro eliminado correctamente.');
            $this->cargarEstadistica();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function nuevoRegistroPeriodo(): void
    {
        $this->resetForm();
        $this->mostrarFormularioPeriodo = true;
    }

    public function modalHasStop(): void
    {
        $this->mostrarFormularioPeriodo = false;
    }

    public function resetForm(): void
    {
        $this->reset(['periodoId', 'periodo']);

        $this->periodo = [
            'plan_empleado_id' => null,
            'codigo' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'observaciones' => null,
            'motivo_modificacion' => null,
        ];

        $this->filtros = [
            'plan_empleado_id' => null,
            'anio' => null
        ];
    }
    public function updatedFiltros(): void
    {
        $this->resetPage();
    }
    public function render(PlanillaPeriodoServicio $servicio)
    {
        return view('livewire.gestion-planilla.periodos-planilla-component', [
            'periodos' => $servicio->obtenerPaginacion($this->filtros),
            'empleados' => $this->empleados,
            'tiposAsistencia' => $this->tiposAsistencia,
        ]);
    }
}