<?php

namespace App\Livewire\Planilla\Empleado;

use App\Constants\Permisos;
use App\Exceptions\ErrorControlado;
use App\Models\PlanCargo;
use App\Models\PlanContrato;
use App\Models\PlanContratoAsignacion;
use App\Models\PlanContratoRemuneracion;
use App\Models\PlanEmpleado;
use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Historial laboral" del panel del empleado (DESIGN.md): cada contrato (periodo legal) con su línea de tiempo
 * de asignaciones (cargo y grupo) y remuneraciones (sueldo, básica, bonificación…). Un cambio dentro del contrato es
 * una fila nueva con su fecha "desde"; no hay que cerrar la anterior. Todo se guarda con PlanillaLaboralProceso.
 */
class EmpleadoContratosTabComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public int $empleadoId;

    /** null | contrato | asignacion | remuneracion | finalizar */
    public ?string $panel = null;
    public ?int $contratoId = null;
    public ?int $particulaId = null;

    public array $c = [];
    public array $a = [];
    public array $r = [];
    public array $cierre = [];

    // ------------------------------------------------------------------ contratos

    public function nuevoContrato(): void
    {
        $this->abrirPanel('contrato');
        $ultimo = $this->empleado()->contratos()->orderByDesc('fecha_inicio')->first();
        $this->c = ['tipo_contrato' => $ultimo?->tipo_contrato ?? 'indefinido', 'tipo_planilla' => $ultimo?->tipo_planilla ?? 'agraria',
            'tipo_ingreso' => $ultimo?->tipo_ingreso ?? 'planilla', 'fecha_inicio' => null, 'fecha_fin' => null, 'fecha_fin_prueba' => null];
        // Sugerencia: lo último que tuvo (recontratación); se puede cambiar
        $asig = $ultimo?->asignacionEn($ultimo->fecha_fin ?? now());
        $rem = $ultimo?->remuneracionEn($ultimo->fecha_fin ?? now());
        $this->a = ['plan_cargo_id' => $asig?->plan_cargo_id, 'grupo_codigo' => $asig?->grupo_codigo, 'motivo' => null];
        $this->r = $this->datosRemuneracion($rem);
    }

    public function editarContrato(int $id): void
    {
        $contrato = $this->contrato($id);
        $this->abrirPanel('contrato', $contrato->id);
        $this->c = ['tipo_contrato' => $contrato->tipo_contrato, 'tipo_planilla' => $contrato->tipo_planilla, 'tipo_ingreso' => $contrato->tipo_ingreso,
            'fecha_inicio' => $contrato->fecha_inicio->toDateString(), 'fecha_fin' => $contrato->fecha_fin?->toDateString(),
            'fecha_fin_prueba' => $contrato->fecha_fin_prueba?->toDateString(),
            'motivo_cese_sunat' => $contrato->motivo_cese_sunat, 'comentario_cese' => $contrato->comentario_cese];
    }

    public function guardarContrato(PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_CONTRATOS, 'contratos');
            if ($this->contratoId) {
                $proceso->guardarContrato($this->empleado(), $this->c, $this->contrato($this->contratoId));
                $mensaje = 'Contrato actualizado.';
            } else {
                $this->exigir(Permisos::PERSONAL_CARGOS, 'el cargo y grupo');
                $this->exigir(Permisos::PERSONAL_REMUNERACIONES, 'la remuneración');
                $proceso->contratar($this->empleado(), $this->c, $this->a, $this->r);
                $mensaje = 'Contrato creado con su cargo, grupo y remuneración iniciales.';
            }
            $this->terminar($mensaje);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function abrirFinalizar(int $id): void
    {
        $this->abrirPanel('finalizar', $this->contrato($id)->id);
        $this->cierre = ['fecha_fin' => null, 'motivo_cese_sunat' => null, 'comentario_cese' => null];
    }

    public function confirmarFinalizar(PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_CONTRATOS, 'contratos');
            if (blank($this->cierre['fecha_fin'] ?? null) || blank($this->cierre['motivo_cese_sunat'] ?? null)) {
                throw new ErrorControlado('Indica la fecha de fin y el motivo de cese.');
            }
            $proceso->finalizarContrato($this->contrato($this->contratoId), $this->cierre['fecha_fin'], $this->cierre['motivo_cese_sunat'], $this->cierre['comentario_cese'] ?? null);
            $this->terminar('Contrato finalizado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function reabrirContrato(int $id, PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_CONTRATOS, 'contratos');
            $proceso->reabrirContrato($this->contrato($id));
            $this->terminar('Contrato reabierto (sin fecha de fin).');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function eliminarContrato(int $id, PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_CONTRATOS, 'contratos');
            $proceso->eliminarContrato($this->contrato($id));
            $this->terminar('Contrato eliminado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ asignaciones (cargo y grupo)

    /** Cambio de cargo o grupo dentro del contrato: se propone lo vigente y una fecha. */
    public function nuevaAsignacion(int $contratoId): void
    {
        $contrato = $this->contrato($contratoId);
        $actual = $contrato->asignacionEn();
        $this->abrirPanel('asignacion', $contrato->id);
        $this->a = ['desde' => null, 'plan_cargo_id' => $actual?->plan_cargo_id, 'grupo_codigo' => $actual?->grupo_codigo, 'motivo' => null];
    }

    public function editarAsignacion(int $id): void
    {
        $x = PlanContratoAsignacion::whereHas('contrato', fn($q) => $q->where('plan_empleado_id', $this->empleadoId))->findOrFail($id);
        $this->abrirPanel('asignacion', $x->plan_contrato_id, $x->id);
        $this->a = ['desde' => $x->desde->toDateString(), 'plan_cargo_id' => $x->plan_cargo_id, 'grupo_codigo' => $x->grupo_codigo, 'motivo' => $x->motivo];
    }

    public function guardarAsignacion(PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_CARGOS, 'el cargo y grupo');
            $contrato = $this->contrato($this->contratoId);
            // En una transacción: si la fecha cambió se quita la fila anterior; si algo falla no se pierde nada
            DB::transaction(function () use ($proceso, $contrato) {
                $this->moverSiCambiaFecha(PlanContratoAsignacion::class, $this->a['desde'] ?? null);
                $proceso->guardarAsignacion($contrato, $this->a);
            });
            $this->terminar('Cargo y grupo guardados.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function eliminarAsignacion(int $id, PlanillaLaboralProceso $proceso): void
    {
        $this->eliminarParticula(PlanContratoAsignacion::class, $id, Permisos::PERSONAL_CARGOS, $proceso);
    }

    // ------------------------------------------------------------------ remuneraciones

    public function nuevaRemuneracion(int $contratoId): void
    {
        $contrato = $this->contrato($contratoId);
        $this->abrirPanel('remuneracion', $contrato->id);
        $this->r = ['desde' => null] + $this->datosRemuneracion($contrato->remuneracionEn());
    }

    public function editarRemuneracion(int $id): void
    {
        $x = PlanContratoRemuneracion::whereHas('contrato', fn($q) => $q->where('plan_empleado_id', $this->empleadoId))->findOrFail($id);
        $this->abrirPanel('remuneracion', $x->plan_contrato_id, $x->id);
        $this->r = ['desde' => $x->desde->toDateString()] + $this->datosRemuneracion($x) + ['motivo' => $x->motivo];
    }

    public function guardarRemuneracion(PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir(Permisos::PERSONAL_REMUNERACIONES, 'la remuneración');
            $contrato = $this->contrato($this->contratoId);
            DB::transaction(function () use ($proceso, $contrato) {
                $this->moverSiCambiaFecha(PlanContratoRemuneracion::class, $this->r['desde'] ?? null);
                $proceso->guardarRemuneracion($contrato, $this->r);
            });
            $this->terminar('Remuneración guardada.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function eliminarRemuneracion(int $id, PlanillaLaboralProceso $proceso): void
    {
        $this->eliminarParticula(PlanContratoRemuneracion::class, $id, Permisos::PERSONAL_REMUNERACIONES, $proceso);
    }

    // ------------------------------------------------------------------ apoyo

    private function datosRemuneracion(?PlanContratoRemuneracion $x): array
    {
        return [
            'sueldo' => $x?->sueldo, 'remuneracion_basica' => $x?->remuneracion_basica, 'bonificacion' => $x?->bonificacion,
            'compensacion_vacacional' => $x?->compensacion_vacacional, 'modalidad_pago' => $x?->modalidad_pago ?? 'mensual',
            'beneficios_mensuales' => (int) (bool) $x?->beneficios_mensuales, 'motivo' => null,
        ];
    }

    /** Al editar una partícula y cambiar su fecha, la fila anterior se quita (la clave es contrato + desde). */
    private function moverSiCambiaFecha(string $modelo, ?string $desde): void
    {
        if (!$this->particulaId) {
            return;
        }
        $x = $modelo::find($this->particulaId);
        if ($x && $desde && $x->desde->toDateString() !== $desde) {
            $x->delete();
        }
    }

    private function eliminarParticula(string $modelo, int $id, string $permiso, PlanillaLaboralProceso $proceso): void
    {
        try {
            $this->exigir($permiso, 'esta información');
            $x = $modelo::whereHas('contrato', fn($q) => $q->where('plan_empleado_id', $this->empleadoId))->findOrFail($id);
            if ($x->desde->eq($x->contrato->fecha_inicio)) {
                throw new ErrorControlado('No se puede quitar la del inicio del contrato: edítala en su lugar.');
            }
            $proceso->eliminarParticula($x);
            $this->terminar('Cambio quitado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function exigir(string $permiso, string $que): void
    {
        if (!Gate::allows($permiso)) {
            throw new ErrorControlado("No tienes permiso para modificar {$que} (permiso: {$permiso}).");
        }
    }

    private function empleado(): PlanEmpleado
    {
        return PlanEmpleado::withTrashed()->findOrFail($this->empleadoId);
    }

    private function contrato(?int $id): PlanContrato
    {
        return PlanContrato::where('plan_empleado_id', $this->empleadoId)->findOrFail($id);
    }

    private function abrirPanel(string $panel, ?int $contratoId = null, ?int $particulaId = null): void
    {
        $this->resetErrorBag();
        $this->panel = $panel;
        $this->contratoId = $contratoId;
        $this->particulaId = $particulaId;
    }

    public function cerrarPanel(): void
    {
        $this->reset(['panel', 'contratoId', 'particulaId', 'c', 'a', 'r', 'cierre']);
    }

    private function terminar(string $mensaje): void
    {
        $this->alert('success', $mensaje);
        $this->cerrarPanel();
        // Refresca el perfil y la lista de empleados (estado de contrato, grupo)
        $this->dispatch('empleadoActualizado');
        $this->dispatch('empleadoGuardado');
    }

    public function render()
    {
        $contratos = PlanContrato::with(['asignaciones.cargo', 'asignaciones.grupo', 'remuneraciones'])
            ->where('plan_empleado_id', $this->empleadoId)->orderByDesc('fecha_inicio')->get();
        return view('livewire.planilla.empleado.empleado-contratos-tab-component', [
            'contratos' => $contratos,
            'cargos' => PlanCargo::where('activo', true)->orderBy('nombre')->get(),
            'contratoPanel' => $this->contratoId ? $contratos->firstWhere('id', $this->contratoId) : null,
        ]);
    }
}
