<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Costos\ConsolidarCostoManoObraServicio;
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

                PlanMensualPersonal::where('plan_empleado_id', $fila['plan_empleado_id'])
                    ->whereHas('planMensual', fn($q) => $q->where('mes', $this->mes)->where('anio', $this->anio))
                    ->update([
                        'vacaciones_plame_personalizado' => $fila['vacaciones_plame_personalizado'] ?? null,
                        'bonificacion_asistencia' => $fila['bonificacion_asistencia'] ?? null,
                    ]);
            }
            // Bono por asistencia NO se consolida por campo (irá a "costo FDM
            // personalizado" más adelante). Lo único que necesita refrescarse
            // aquí es el bono de productividad, que sí vive por campo.
            $fechaInicioMes = Carbon::create($this->anio, $this->mes, 1)->startOfMonth()->format('Y-m-d');
            $fechaFinMes = Carbon::create($this->anio, $this->mes, 1)->endOfMonth()->format('Y-m-d');

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
        return view('livewire.gestion-planilla.planilla-vacaciones-bonos-component');
    }
}