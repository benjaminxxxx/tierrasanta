<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;
use App\Models\PlanEmpleado;
use App\Services\Modulos\Planilla\GestionPlanillaEmpleados;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class GestionPlanillaEmpleadosComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
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
    public ?string $estadoContrato = null;
    // valores: null | 'con' | 'sin'
    protected $resetOnUpdate = [
        'filtro',
        'planCargoId',
        'planDescuentoSpCodigo',
        'planGrupoCodigo',
        'planTipoPlanilla',
        'planGenero',
        'planEliminados',
    ];
    protected $listeners = ['empleadoGuardado' => '$refresh'];
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
            $this->alert('error', $th->getMessage());
        }
    }
    public function restaurarEmpleado($id)
    {
        try {
            app(GestionPlanillaEmpleados::class)->restaurarEmpleado($id);
            $this->resetPage();
            $this->alert('success', 'Restaurado correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function abrirFormCambioMasivoSueldo()
    {
        $lista = PlanEmpleado::with(['ultimoContrato', 'ultimoSueldo'])
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'nombre' => mb_strtoupper(trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}")),
                    'grupo_codigo' => optional($e->ultimoContrato)->grupo_codigo ?? '-',
                    'cargo_codigo' => optional($e->ultimoContrato)->cargo_codigo ?? '-',
                    'tipo_planilla' => mb_strtoupper(optional($e->ultimoContrato)->tipo_planilla ?? '-'), // "1" o "2"
                    'sueldo_actual' => optional($e->ultimoSueldo)->sueldo ?? 0,
                    'nuevo_sueldo' => optional($e->ultimoSueldo)->sueldo ?? 0,
                    'sueldo_vigente' => optional($e->ultimoSueldo)->fecha_inicio
                        ? formatear_fecha($e->ultimoSueldo->fecha_inicio)
                        : '-',
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
            return $this->alert('error', $th->getMessage());
        }

    }
    public function updated($propertyName)
    {
        if (in_array($propertyName, $this->resetOnUpdate, true)) {
            $this->resetPage();
        }
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

        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados', [
            'empleados' => $planEmpleados,
        ]);
    }
}