<?php

namespace App\Livewire\Planilla\Empleado;

use App\Constants\Permisos;
use App\Exceptions\ErrorControlado;
use App\Models\PlanEmpleado;
use App\Models\PlanEmpleadoPago;
use App\Models\PlanEmpleadoPension;
use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Pensión y pago" (DESIGN.md): partículas del empleado, solo con "desde". Siguen al empleado aunque cambie de
 * contrato o lo recontraten. Cambiar de AFP u ONP no toca el contrato. Se guarda con PlanillaLaboralProceso.
 */
class EmpleadoPensionPagoTabComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public int $empleadoId;

    /** null | pension | pago */
    public ?string $panel = null;
    public ?int $particulaId = null;
    public array $p = [];

    private const PAGO = ['metodo_pago', 'banco', 'tipo_cuenta', 'moneda_cuenta', 'numero_cuenta', 'banco_secundario', 'tipo_cuenta_secundaria',
        'moneda_cuenta_secundaria', 'numero_cuenta_secundaria', 'motivo'];

    public function nuevaPension(): void
    {
        $actual = $this->empleado()->pensionEn();
        $this->abrir('pension', null, ['desde' => null, 'plan_sp_codigo' => $actual?->plan_sp_codigo, 'esta_jubilado' => (int) (bool) $actual?->esta_jubilado,
            'suspension_cuarta' => (int) (bool) $actual?->suspension_cuarta, 'motivo' => null]);
    }

    public function editarPension(int $id): void
    {
        $x = PlanEmpleadoPension::where('plan_empleado_id', $this->empleadoId)->findOrFail($id);
        $this->abrir('pension', $x->id, ['desde' => $x->desde->toDateString(), 'plan_sp_codigo' => $x->plan_sp_codigo,
            'esta_jubilado' => (int) $x->esta_jubilado, 'suspension_cuarta' => (int) $x->suspension_cuarta, 'motivo' => $x->motivo]);
    }

    public function nuevoPago(): void
    {
        $actual = $this->empleado()->pagoEn();
        $datos = ['desde' => null];
        foreach (self::PAGO as $c) {
            $datos[$c] = $c === 'motivo' ? null : $actual?->{$c};
        }
        $this->abrir('pago', null, $datos);
    }

    public function editarPago(int $id): void
    {
        $x = PlanEmpleadoPago::where('plan_empleado_id', $this->empleadoId)->findOrFail($id);
        $this->abrir('pago', $x->id, ['desde' => $x->desde->toDateString()] + $x->only(self::PAGO));
    }

    public function guardar(PlanillaLaboralProceso $proceso): void
    {
        try {
            [$permiso, $modelo] = $this->panel === 'pension' ? [Permisos::PERSONAL_PENSIONES, PlanEmpleadoPension::class] : [Permisos::PERSONAL_PAGOS, PlanEmpleadoPago::class];
            if (!Gate::allows($permiso)) {
                throw new ErrorControlado("No tienes permiso para modificar esta información (permiso: {$permiso}).");
            }
            DB::transaction(function () use ($proceso, $modelo) {
                // Si al editar cambió la fecha, la fila anterior se quita (la clave es empleado + desde)
                $anterior = $this->particulaId ? $modelo::find($this->particulaId) : null;
                if ($anterior && ($this->p['desde'] ?? null) && $anterior->desde->toDateString() !== $this->p['desde']) {
                    $anterior->delete();
                }
                $this->panel === 'pension' ? $proceso->guardarPension($this->empleado(), $this->p) : $proceso->guardarPago($this->empleado(), $this->p);
            });
            $this->alert('success', 'Guardado.');
            $this->cerrarPanel();
            $this->dispatch('empleadoActualizado');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function eliminar(string $tipo, int $id, PlanillaLaboralProceso $proceso): void
    {
        try {
            [$permiso, $modelo] = $tipo === 'pension' ? [Permisos::PERSONAL_PENSIONES, PlanEmpleadoPension::class] : [Permisos::PERSONAL_PAGOS, PlanEmpleadoPago::class];
            if (!Gate::allows($permiso)) {
                throw new ErrorControlado("No tienes permiso para modificar esta información (permiso: {$permiso}).");
            }
            $proceso->eliminarParticula($modelo::where('plan_empleado_id', $this->empleadoId)->findOrFail($id));
            $this->alert('success', 'Quitado.');
            $this->dispatch('empleadoActualizado');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function abrir(string $panel, ?int $id, array $datos): void
    {
        $this->resetErrorBag();
        $this->panel = $panel;
        $this->particulaId = $id;
        $this->p = $datos;
    }

    public function cerrarPanel(): void
    {
        $this->reset(['panel', 'particulaId', 'p']);
    }

    private function empleado(): PlanEmpleado
    {
        return PlanEmpleado::withTrashed()->findOrFail($this->empleadoId);
    }

    public function render()
    {
        $empleado = $this->empleado()->load(['pensiones.descuento', 'pagos']);
        return view('livewire.planilla.empleado.empleado-pension-pago-tab-component', [
            'pensiones' => $empleado->pensiones->sortByDesc('desde'),
            'pagos' => $empleado->pagos->sortByDesc('desde'),
            'pensionActual' => $empleado->pensionEn(),
            'pagoActual' => $empleado->pagoEn(),
        ]);
    }
}
