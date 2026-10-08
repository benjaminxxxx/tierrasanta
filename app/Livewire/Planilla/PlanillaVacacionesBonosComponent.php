<?php

namespace App\Livewire\Planilla;

use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoAsistencia;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use App\Services\Planilla\PlanillaServicio;
use App\Services\Planilla\ResumenAsistenciaMensualServicio;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaVacacionesBonosComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    public $mes;
    public $anio;
    public $planilla = [];
    public $hasUnsavedChanges = false;
    public $modifiedRowIndexes = [];
    protected $listeners = ['vacacionesCalculadas' => 'cargarPlanillaVacacionesBonos'];
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;

        $this->cargarPlanillaVacacionesBonos(false);
    }
    public function cargarPlanillaVacacionesBonos($isDispatched = true)
    {
        $this->planilla = app(ResumenAsistenciaMensualServicio::class)->obtenerResumenPorMes($this->mes, $this->anio);
        if ($isDispatched) {
            $this->dispatch('setplanilla', tableData: $this->planilla);
        }
    }
    public function guardarInformacionBonoVacaciones(array $filas)
    {
        try {
            foreach ($filas as $fila) {
                if (empty($fila['plan_empleado_id'])) {
                    continue;
                }

                $persona = PlanMensualPersonal::with('planMensual')->where('plan_empleado_id', $fila['plan_empleado_id'])
                    ->whereHas('planMensual', fn($q) => $q->where('mes', $this->mes)->where('anio', $this->anio))
                    ->first();
                if (!$persona) {
                    continue;
                }

                $persona->update(['bonificacion_asistencia' => $fila['bonificacion_asistencia'] ?? null]);

                // Vacaciones personalizadas = ajuste del 0118 (el mismo de "Ajustes PLAME"): recalcula en cadena la
                // remuneración bruta, gratificación, CTS, descuentos, EsSalud, neto y el exceso pagado en negro
                $personalizado = $fila['vacaciones_plame_personalizado'] ?? null;
                $ajusteActual = $persona->ajustePlame('0118');
                app(\App\Services\Planilla\Plame\PlanillaPlameProceso::class)->guardarAjuste($persona, '0118',
                    $personalizado === '' ? null : $personalizado, $ajusteActual['motivo'] ?? 'Vacaciones personalizadas');
            }
            // Bono por asistencia NO se consolida por campo (irá a "costo FDM
            // personalizado" más adelante). Lo único que necesita refrescarse
            // aquí es el bono de productividad, que sí vive por campo.
            $fechaInicioMes = Carbon::create($this->anio, $this->mes, 1)->startOfMonth()->format('Y-m-d');
            $fechaFinMes = Carbon::create($this->anio, $this->mes, 1)->endOfMonth()->format('Y-m-d');

            // El Excel descargable lleva las vacaciones personalizadas (ajuste 0118): se rehace
            $plan = \App\Models\PlanMensual::where('mes', $this->mes)->where('anio', $this->anio)->first();
            if ($plan && $plan->excel) {
                $plan->update(['excel' => app(PlanillaServicio::class)->generarExcelPlanilla($this->mes, $this->anio)]);
            }

            app(ConsolidarCostoManoObraServicio::class)
                ->consolidarPlanillaEnRango($fechaInicioMes, $fechaFinMes, ['bono_productividad']);


            $this->modifiedRowIndexes = [];
            $this->hasUnsavedChanges = false;
            $this->cargarPlanillaVacacionesBonos();
            $this->alert('success', 'Vacaciones y bonos guardados correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render()
    {
        return view('livewire.planilla.planilla-vacaciones-bonos-component');
    }
}