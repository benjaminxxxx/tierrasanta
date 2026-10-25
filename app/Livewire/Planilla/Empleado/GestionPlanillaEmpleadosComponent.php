<?php

namespace App\Livewire\Planilla\Empleado;
use App\Models\PlanEmpleado;
use App\Services\Planilla\Modulos\GestionPlanillaEmpleados;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class GestionPlanillaEmpleadosComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination, \App\Traits\HandlesAlerts;
    public $planCargoId;
    public $planDescuentoSpCodigo;
    public $planGrupoCodigo;
    public $filtro;
    public $planGenero;
    public $planEliminados;
    public $planTipoPlanilla;
    public $mostrarFormularioCambioSueldos = false;
    public $mesVigencia;
    public $anioVigencia;
    // Por defecto solo empleados con contrato vigente hoy.
    // valores: 'vigente' | 'no_vigente' | 'sin' | '' (todos)
    public ?string $estadoContrato = 'vigente';
    protected $resetOnUpdate = [
        'filtro',
        'planCargoId',
        'planDescuentoSpCodigo',
        'planGrupoCodigo',
        'planTipoPlanilla',
        'planGenero',
        'planEliminados',
    ];
    protected $listeners = ['empleadoGuardado' => '$refresh','cargoAsignado'=>'$refresh'];
    public function mount()
    {
        $this->mesVigencia = Carbon::now()->format('m');
        $this->anioVigencia = Carbon::now()->format('Y');
    }
    public function eliminarEmpleado($id)
    {
        try {
            app(GestionPlanillaEmpleados::class)->eliminarEmpleado($id);
            $this->resetPage();
            $this->alert('success', 'Eliminado correctamente');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function restaurarEmpleado($id)
    {
        try {
            app(GestionPlanillaEmpleados::class)->restaurarEmpleado($id);
            $this->resetPage();
            $this->alert('success', 'Restaurado correctamente');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function abrirFormCambioMasivoSueldo()
    {
        // Solo quienes tienen contrato vigente: el nuevo sueldo es una remuneración nueva en ese contrato
        $lista = PlanEmpleado::conContratoVigente()->with(['contratos.asignaciones', 'contratos.remuneraciones'])->alfabetico()
            ->get()
            ->map(function ($e) {
                $contrato = $e->contratoEn();
                $rem = $contrato?->remuneracionEn();
                return [
                    'id' => $e->id,
                    'nombre' => mb_strtoupper(trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}")),
                    'grupo_codigo' => $contrato?->asignacionEn()?->grupo_codigo ?? '-',
                    'tipo_planilla' => mb_strtoupper($contrato?->tipo_planilla ?? '-'),
                    'sueldo_actual' => (float) ($rem?->sueldo ?? 0),
                    'nuevo_sueldo' => (float) ($rem?->sueldo ?? 0),
                    'sueldo_vigente' => $rem ? formatear_fecha($rem->desde) : '-',
                    'seleccionado' => false,
                ];
            })
            ->values()
            ->all();

        $this->mostrarFormularioCambioSueldos = true;

        // importante: dispara el evento para que Alpine cargue la lista
        $this->dispatch('ejecutarCambioSueldos', trabajadores: $lista);
    }
    public function guardarCambiosSueldos($cambios)
    {
        try {
            app(GestionPlanillaEmpleados::class)->guardarSueldosMasivos(
                $cambios,
                $this->mesVigencia,
                $this->anioVigencia
            );
            $this->alert('success', 'Sueldos modificados correctamente.');

        } catch (\Throwable $th) {
            return $this->errorAlert($th);
        }

    }
    public function updated($propertyName)
    {
        if (in_array($propertyName, $this->resetOnUpdate, true)) {
            $this->resetPage();
        }
    }
    public function updatedPlanEliminados($value)
    {
        // Un empleado eliminado casi nunca tiene contrato vigente: al ver eliminados se muestran todos.
        $this->estadoContrato = $value === 'eliminados' ? '' : 'vigente';
        $this->resetPage();
    }
    public function updatedEstadoContrato($value)
    {
        $this->resetPage();
        if ($value === 'sin') {
            $this->planCargoId = null;
            $this->planDescuentoSpCodigo = null;
            $this->planGrupoCodigo = null;
            $this->planTipoPlanilla = null;
        }
    }

    public function render()
    {
        $filtros = [
            'cargo_id' => $this->planCargoId,
            'descuento_sp_codigo' => $this->planDescuentoSpCodigo,
            'grupo_codigo' => $this->planGrupoCodigo,
            'filtro' => $this->filtro,
            'genero' => $this->planGenero,
            'estado' => $this->planEliminados,
            'tipo_planilla' => $this->planTipoPlanilla,
            'estado_contrato' => $this->estadoContrato,
        ];

        $planEmpleados = app(GestionPlanillaEmpleados::class)->buscarEmpleado($filtros);

        return view('livewire.planilla.empleado.gestion-planilla-empleados', [
            'empleados' => $planEmpleados,
        ]);
    }
}