<?php

namespace App\Livewire\Planilla;

use App\Constants\Permisos;
use App\Models\PlanMensual;
use App\Models\PlanMensualPersonal;
use App\Services\Planilla\Plame\PlanillaPlameProceso;
use App\Services\Planilla\Plame\PlanillaPlameReglas;
use App\Services\Planilla\PlanillaServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Planilla → Ajustes PLAME: montos puestos a mano en conceptos del PLAME de un trabajador (vacaciones calculadas
 * aparte, cuadre de vida ley/SCTR contra la factura del seguro, redondeos de la planilla oficial…). Un solo lugar
 * para verlos y administrarlos; el sistema conserva lo que había calculado y recalcula en cadena.
 */
class PlanillaPlameAjustesComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public $mes;
    public $anio;

    // Formulario
    public $personalId;
    public $codigo;
    public $monto;
    public $motivo;

    public function mount($mes, $anio): void
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function editar(int $personalId, string $codigo): void
    {
        $p = $this->persona($personalId);
        $ajuste = $p?->ajustePlame($codigo);
        $this->personalId = $personalId;
        $this->codigo = $codigo;
        $this->monto = $ajuste['monto'] ?? null;
        $this->motivo = $ajuste['motivo'] ?? null;
        $this->resetErrorBag();
    }

    public function limpiar(): void
    {
        $this->reset(['personalId', 'codigo', 'monto', 'motivo']);
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $this->authorize(Permisos::PLANILLA_BLANCO_GESTIONAR);
        $this->validate([
            'personalId' => 'required',
            'codigo' => 'required|in:' . implode(',', array_keys(PlanillaPlameReglas::ajustables())),
            'monto' => 'required|numeric|min:0',
            'motivo' => 'nullable|string|max:200',
        ], [], ['personalId' => 'trabajador', 'codigo' => 'concepto']);

        $this->aplicar($this->codigo, $this->monto, $this->motivo);
        $this->limpiar();
    }

    public function quitar(int $personalId, string $codigo): void
    {
        $this->authorize(Permisos::PLANILLA_BLANCO_GESTIONAR);
        $this->personalId = $personalId;
        $this->aplicar($codigo, null, null);
        $this->limpiar();
    }

    private function aplicar(string $codigo, $monto, ?string $motivo): void
    {
        try {
            $p = $this->persona((int) $this->personalId);
            if (!$p) {
                $this->alert('error', 'El trabajador no está en la planilla de este mes.');
                return;
            }
            app(PlanillaPlameProceso::class)->guardarAjuste($p, $codigo, $monto, $motivo);
            $this->actualizarExcel();
            $this->alert('success', $monto === null ? 'Ajuste quitado: vuelve el valor calculado.' : 'Ajuste guardado y recalculado.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** El Excel descargable lleva los ajustes: se rehace al cambiarlos. */
    private function actualizarExcel(): void
    {
        $plan = PlanMensual::where('mes', $this->mes)->where('anio', $this->anio)->first();
        if ($plan && $plan->planilla()->exists()) {
            $plan->update(['excel' => app(PlanillaServicio::class)->generarExcelPlanilla($this->mes, $this->anio)]);
        }
    }

    private function persona(int $id): ?PlanMensualPersonal
    {
        return PlanMensualPersonal::with('planMensual')->whereKey($id)
            ->whereHas('planMensual', fn($q) => $q->where('mes', $this->mes)->where('anio', $this->anio))->first();
    }

    public function render()
    {
        $personal = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $this->mes)->where('anio', $this->anio))
            ->orderBy('orden')->get();
        $conceptos = PlanillaPlameReglas::ajustables();

        $ajustes = [];
        foreach ($personal as $p) {
            foreach ($p->plame_ajustes ?? [] as $codigo => $a) {
                $calculado = $p->calculadoPlame((string) $codigo);
                $ajustes[] = [
                    'personal_id' => $p->id,
                    'nombres' => $p->nombres,
                    'codigo' => (string) $codigo,
                    'concepto' => $conceptos[$codigo] ?? $codigo,
                    'calculado' => $calculado,
                    'monto' => (float) $a['monto'],
                    'diferencia' => $calculado === null ? null : round((float) $a['monto'] - $calculado, 2),
                    'motivo' => $a['motivo'] ?? null,
                ];
            }
        }

        // Valor que hoy calcula el sistema para lo elegido en el formulario (referencia al escribir el monto)
        $referencia = null;
        if ($this->personalId && $this->codigo && ($p = $personal->firstWhere('id', (int) $this->personalId))) {
            $referencia = $p->calculadoPlame($this->codigo) ?? (float) $p->{PlanillaPlameReglas::columna($this->codigo)};
        }

        return view('livewire.planilla.planilla-plame-ajustes-component', [
            'personal' => $personal,
            'conceptos' => $conceptos,
            'ajustes' => $ajustes,
            'referencia' => $referencia,
        ]);
    }
}
