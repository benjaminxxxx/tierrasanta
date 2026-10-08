<?php

namespace App\Livewire\Planilla;

use App\Constants\Permisos;
use App\Models\CostoMensual;
use App\Models\PlanMensual;
use App\Models\PlanOficinaPersonal;
use App\Services\Planilla\Oficina\PlanillaOficinaExcel;
use App\Services\Planilla\Oficina\PlanillaOficinaProceso;
use App\Traits\HandlesAlerts;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Planilla → Planilla oficina (régimen general): todos los que tienen contrato de oficina o general, en planilla o por
 * recibo por honorarios. Calcula el costo administrativo del mes (blanco y negro) para compararlo con el que se pone
 * a mano en Costos mensuales, hasta que sea confiable y se use directo. Genera el Excel (hojas ADM, EMPLEADOS y
 * BENEFICIOS).
 */
#[Title('Planilla Oficina')]
class PlanillaOficinaComponent extends Component
{
    use LivewireAlert, HandlesAlerts, ConSelectorMes;

    // Modal de ajuste
    public bool $modalAjuste = false;
    public $ajusteFilaId;
    public $ajusteColumna;
    public $ajusteMonto;
    public $ajusteMotivo;
    public $ajusteComprobante;

    public function mount(): void
    {
        $this->inicializarMesAnio();
    }

    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }

    public function generar(): void
    {
        $this->authorize(Permisos::PLANILLA_OFICINA_GESTIONAR);
        try {
            $r = app(PlanillaOficinaProceso::class)->generar((int) $this->mes, (int) $this->anio);
            $this->actualizarExcel();
            $this->alert('success', "Planilla oficina generada: {$r['personas']} persona(s).");
            foreach ($r['avisos'] as $aviso) {
                $this->alert('warning', $aviso);
            }
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    // ------------------------------------------------------------------ ajustes

    public function abrirAjuste(int $filaId, ?string $columna = null): void
    {
        $fila = $this->fila($filaId);
        if (!$fila) {
            return;
        }
        $this->resetErrorBag();
        $this->ajusteFilaId = $filaId;
        $this->ajusteColumna = $columna;
        $this->ajusteComprobante = $fila->comprobante;
        $this->cargarAjuste($fila);
        $this->modalAjuste = true;
    }

    public function updatedAjusteColumna(): void
    {
        if ($fila = $this->fila((int) $this->ajusteFilaId)) {
            $this->cargarAjuste($fila);
        }
    }

    private function cargarAjuste(PlanOficinaPersonal $fila): void
    {
        $ajuste = $this->ajusteColumna ? $fila->ajuste($this->ajusteColumna) : null;
        $this->ajusteMonto = $ajuste['monto'] ?? null;
        $this->ajusteMotivo = $ajuste['motivo'] ?? null;
    }

    public function guardarAjuste(?bool $quitar = false): void
    {
        $this->authorize(Permisos::PLANILLA_OFICINA_GESTIONAR);
        $fila = $this->fila((int) $this->ajusteFilaId);
        if (!$fila) {
            return;
        }
        $proceso = app(PlanillaOficinaProceso::class);
        $proceso->guardarComprobante($fila, $this->ajusteComprobante);
        if ($this->ajusteColumna) {
            $this->validate(['ajusteColumna' => 'in:' . implode(',', array_keys(PlanillaOficinaProceso::AJUSTABLES))], [], ['ajusteColumna' => 'concepto']);
            if (!$quitar) {
                $this->validate(['ajusteMonto' => 'required|numeric|min:0'], [], ['ajusteMonto' => 'monto']);
            }
            $proceso->guardarAjuste($fila->refresh(), $this->ajusteColumna, $quitar ? null : $this->ajusteMonto, $this->ajusteMotivo);
        }
        $this->actualizarExcel();
        $this->modalAjuste = false;
        $this->alert('success', $quitar ? 'Ajuste quitado: vuelve el valor calculado.' : 'Guardado y recalculado.');
    }

    /** El Excel descargable se rehace con cada cambio para que no quede desactualizado. */
    private function actualizarExcel(): void
    {
        $plan = PlanMensual::where('mes', (int) $this->mes)->where('anio', (int) $this->anio)->first();
        if ($plan && PlanOficinaPersonal::where('plan_mensual_id', $plan->id)->exists()) {
            $plan->update(['excel_oficina' => app(PlanillaOficinaExcel::class)->generar($plan)]);
        }
    }

    private function fila(int $id): ?PlanOficinaPersonal
    {
        return PlanOficinaPersonal::with('planMensual')->whereKey($id)
            ->whereHas('planMensual', fn($q) => $q->where('mes', (int) $this->mes)->where('anio', (int) $this->anio))->first();
    }

    public function render()
    {
        $plan = PlanMensual::where('mes', (int) $this->mes)->where('anio', (int) $this->anio)->first();
        $filas = $plan ? PlanOficinaPersonal::where('plan_mensual_id', $plan->id)->orderBy('orden')->get() : collect();
        $manual = CostoMensual::where('mes', (int) $this->mes)->where('anio', (int) $this->anio)->first();
        $resumen = PlanillaOficinaExcel::resumenAdm($filas);

        return view('livewire.planilla.planilla-oficina-component', [
            'plan' => $plan,
            'filas' => $filas,
            'costo' => $resumen + [
                'manual_blanco' => $manual?->fijo_administrativo_blanco !== null ? (float) $manual->fijo_administrativo_blanco : null,
                'manual_negro' => $manual?->fijo_administrativo_negro !== null ? (float) $manual->fijo_administrativo_negro : null,
            ],
            'ajustables' => PlanillaOficinaProceso::AJUSTABLES,
            'filaAjuste' => $this->modalAjuste && $this->ajusteFilaId ? $filas->firstWhere('id', (int) $this->ajusteFilaId) : null,
            'urlExcel' => $plan?->excel_oficina && Storage::disk('public')->exists($plan->excel_oficina) ? Storage::disk('public')->url($plan->excel_oficina) : null,
        ]);
    }
}
