<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanEmpleado;
use App\Models\PlanPeriodo;
use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Models\PlanTipoSuspension;
use App\Services\Planilla\PlanillaSuspensionProceso;
use App\Services\Planilla\PlanillaSuspensionServicio;
use App\Services\PlanillaPeriodoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioProcesoSuspensionesPendientes;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class SuspensionesPlanillaComponent extends Component
{
    use LivewireAlert, WithPagination, ConSelectorMes;

    // Propiedades de Estado
    public array $suspensiones = [];
    public array $filtros = [];
    public array $listaEmpleados = [];
    public array $listaSuspensiones = [];
    public array $suspensionesPendientes = [];

    protected $listeners = [];


    protected PlanillaSuspensionServicio $servicio;
    protected PlanillaSuspensionProceso $proceso;
    public function boot(
        PlanillaSuspensionServicio $servicio,
        PlanillaSuspensionProceso $proceso
    ) {
        $this->servicio = $servicio;
        $this->proceso = $proceso;
    }

    public function mount()
    {
        $this->inicializarMesAnio();
        $this->cargarSuspensiones(false);
        $this->cargarEmpleados();
        $this->cargarSuspensionesPendientes();

        $this->listaSuspensiones = PlanTipoSuspension::get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'label' => $q->codigo . ' - ' . $q->descripcion
                ];
            })
            ->toArray();
    }
    public function cargarEmpleados()
    {
        if (!$this->mes || !$this->anio) {
            $this->listaEmpleados = PlanEmpleado::get()
                ->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'label' => $q->nombre_completo
                    ];
                })
                ->toArray();
            return;
        }
        $this->listaEmpleados = app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($this->mes, $this->anio)
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'label' => $q->nombre_completo
                ];
            })
            ->toArray();
    }
    public function cargarSuspensionesPendientes()
    {
        $this->suspensionesPendientes = PlanillaRegistroDiarioProcesoSuspensionesPendientes::obtenerDetalleSuspension($this->mes, $this->anio);
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargarSuspensiones();
    }
    public function agregarSuspensionAlHandsontable($index)
    {
        $pendiente = $this->suspensionesPendientes[$index];

        // Mapear tipo_asistencia a tipo_suspension_id
        $tipoSuspensionId = $this->mapearTipoAsistenciaASuspension($pendiente['tipo_asistencia']);

        // Emitir evento a Alpine/Handsontable
        $this->dispatch('agregar-suspension', data: [
            'plan_empleado_id' => $pendiente['plan_empleado_id'],
            'tipo_suspension_id' => $tipoSuspensionId,
            'observaciones' => null,
            'fecha_inicio' => $pendiente['fecha_inicio'],
            'fecha_fin' => $pendiente['fecha_fin'],
        ]);
    }

    private function mapearTipoAsistenciaASuspension($tipoAsistencia)
    {
        $codigos = PlanTipoSuspension::pluck('id', 'codigo');

        // Mapeo de códigos de asistencia a códigos SUNAT de suspensión
        $mapeo = [
            'F' => $codigos['07'], // Falta injustificada
            'LCG' => $codigos['26'], // Licencia con goce
            'LSG' => $codigos['05'], // Licencia sin goce

            'DM' => $codigos['20'], // Descanso médico (primeros 20 días)
            // Si manejas prolongados, cambia a 21 según tus reglas internas.

            'AM' => $codigos['26'], // Atención médica (permiso con goce)
            // O usar 05 si en tu empresa se descuenta.

            'LM' => $codigos['22'], // Licencia por maternidad
            'V' => $codigos['23'], // Vacaciones
        ];

        return $mapeo[$tipoAsistencia] ?? null;
    }
    public function guardarRegistrosSuspensiones($datos)
    {

        try {
            $resultado = $this->proceso->guardarHandsontable(
                $datos,
                $this->mes,
                $this->anio
            );

            $mensaje = sprintf(
                'Creados: %d | Actualizados: %d | Eliminados: %d',
                $resultado['creados'],
                $resultado['actualizados'],
                $resultado['eliminados']
            );

            if (!empty($resultado['errores'])) {
                $mensaje .= ' | Errores: ' . count($resultado['errores']);
            }

            $this->alert('success', 'Suspensiones guardadas', [
                'text' => $mensaje,
                'position' => 'top-end',
                'timer' => 4000,
            ]);

            $this->cargarSuspensiones();
        } catch (\Exception $e) {
            $this->alert('error', 'Error al guardar', [
                'text' => $e->getMessage(),
                'position' => 'top-end',
                'timer' => 5000,
            ]);
        }
    }
    public function cargarSuspensiones($dispatched = true)
    {
        $mes = $this->normalizarMes($this->mes);
        $anio = $this->normalizarAnio($this->anio);

        $this->suspensiones = $this->servicio->prepararParaHandsontable($mes, $anio);
        $this->cargarEmpleados();
        $this->cargarSuspensionesPendientes();

        if ($dispatched) {
            $this->dispatch('refrescarTablaSuspensiones', data: $this->suspensiones, empleados: $this->listaEmpleados);
        }
    }
    private function normalizarMes($valor): ?int
    {
        // Caso vacío o null
        if ($valor === '' || $valor === null) {
            return null;
        }

        // convertir a entero
        $mes = intval($valor);

        // validar rango real
        return ($mes >= 1 && $mes <= 12) ? $mes : null;
    }

    private function normalizarAnio($valor): ?int
    {
        if ($valor === '' || $valor === null) {
            return null;
        }

        $anio = intval($valor);

        // ajusta el rango según tu sistema
        return ($anio >= 2000 && $anio <= 2100) ? $anio : null;
    }


    public function getTiposAsistenciaProperty()
    {
        return PlanTipoAsistencia::all();
    }


    public function render()
    {
        return view('livewire.gestion-planilla.suspensiones-planilla-component');
    }
}