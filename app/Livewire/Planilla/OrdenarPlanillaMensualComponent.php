<?php


namespace App\Livewire\Planilla;
use App\Services\Planilla\ListaMensual\PlanillaListaMensualProceso;
use App\Services\Planilla\PlanillaMensualServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class OrdenarPlanillaMensualComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $listaPlanilla = [];
    public $mostrarListaPlanillaMensual = false;
    public $dialogOpen = false;
    public $mes;
    public $anio;
    public $fecha;
    public $totalEmpleados = 0;
    public $empleadosOrdenados = [];

    protected $listeners = ['mostrarModalOrdenPlanilla'];

    public function mount()
    {
        $this->fecha = now();
    }

    public function mostrarModalOrdenPlanilla($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->fecha = \Illuminate\Support\Carbon::create((int) $anio, (int) $mes, 1);
        $this->gestionarListaMensual();
        $this->mostrarListaPlanillaMensual = true;
    }

    public function gestionarListaMensual()
    {
        try {
            $this->listaPlanilla = app(PlanillaMensualServicio::class)
                ->obtenerPlanillaXMesAnio($this->mes, $this->anio)
                ->map(function ($planilla) {
                    return [
                        'id' => $planilla->plan_empleado_id,
                        'nombres' => $planilla->nombres,
                        'documento' => $planilla->documento,
                        'orden' => $planilla->orden,
                        'ordenOriginal' => $planilla->orden,
                    ];
                })
                ->toArray();
            $this->totalEmpleados = count($this->listaPlanilla);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /**
     * Solo guarda el orden de los que ya están. Quién está lo decide el sistema: el mes se genera solo con el orden del
     * mes anterior y los contratos nuevos entran al final (PlanillaListaMensualProceso).
     */
    public function guardarOrdenMensualEmpleados()
    {
        try {
            app(PlanillaListaMensualProceso::class)->guardarOrden((int) $this->mes, (int) $this->anio, $this->listaPlanilla);

            $this->mostrarListaPlanillaMensual = false;
            $this->dispatch('actualizarListaPlanillaRegistroDiario');
            $this->alert('success', 'Registro Actualizado Correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.planilla.ordenar-planilla-mensual-component');
    }
}
