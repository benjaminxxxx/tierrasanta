<?php


namespace App\Livewire\GestionPlanilla;
use App\Services\Modulos\Planilla\GestionPlanillaReporteDiario;
use App\Services\PlanillaMensualServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaMensualDetalleServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class OrdenarPlanillaMensualComponent extends Component
{
    use LivewireAlert;
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
            $this->alert('error', $th->getMessage());
        }
    }

    public function agregarPlanilleros()
    {
        $empleados = app(GestionPlanillaReporteDiario::class)
            ->obtenerPlanillaAgraria($this->mes, $this->anio)
            ->toArray();

        if (count($empleados) == 0) {
            $this->alert('warning', 'No hay registros aún');
            return;
        }

        // Fecha del mes anterior
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();

        // Obtener lista de ordenes del mes anterior [empleado_id => orden]
        $ordenesAnteriores = PlanillaMensualDetalleServicio::obtenerOrden(
            $fecha->month,
            $fecha->year
        );

        // 1. Extraer solo los empleados que siguen existiendo y tienen orden previo
        $ordenesValidos = [];

        foreach ($empleados as $emp) {
            $id = $emp['id'];

            if (isset($ordenesAnteriores[$id])) {
                $ordenesValidos[$id] = $ordenesAnteriores[$id];
            }
        }

        // 2. Ordenar por orden del mes pasado
        asort($ordenesValidos); // sort por valor manteniendo el índice del empleado

        // 3. Reindexar los órdenes válidos desde 1 en adelante
        $ordenReindexado = [];
        $n = 1;

        foreach ($ordenesValidos as $idEmp => $oldOrder) {
            $ordenReindexado[$idEmp] = $n;
            $n++;
        }

        // 4. Asignar órdenes nuevos a empleados que no tenían historial
        foreach ($empleados as &$emp) {

            $id = $emp['id'];

            if (isset($ordenReindexado[$id])) {
                // Si tenía orden en el mes pasado (reindexado)
                $emp['orden'] = $ordenReindexado[$id];

            } else {
                // Empleado nuevo → asignar orden incremental
                $emp['orden'] = $n;
                $n++;
            }

            // Guardar ordenOriginal para detectar cambios posteriores
            $emp['ordenOriginal'] = $emp['orden'];
        }
        unset($emp);

        // Guardar el resultado final
        $this->listaPlanilla = $empleados;
    }

    public function guardarOrdenMensualEmpleados()
    {
        try {
            app(GestionPlanillaReporteDiario::class)
                ->guardarOrdenMensualEmpleados($this->mes, $this->anio, $this->listaPlanilla);

            $this->mostrarListaPlanillaMensual = false;
            $this->dispatch('actualizarListaPlanillaRegistroDiario');
            $this->alert('success', 'Registro Actualizado Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-planilla.ordenar-planilla-mensual-component');
    }
}
