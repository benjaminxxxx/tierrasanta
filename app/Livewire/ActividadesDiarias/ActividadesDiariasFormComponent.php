<?php

namespace App\Livewire\ActividadesDiarias;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaGrupo;
use App\Services\Campo\ActividadesServicio;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\CuadrillaServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ActividadesDiariasFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioActividadDiaria = false;
    public $labores = [];
    public $laboresSeleccion = [];
    public $fecha;
    public $laborSeleccionada;
    public $campoSeleccionado;
    public $trabajadorSeleccionado;
    public $horarios_actividad = [
        [
            'inicio' => null,
            'fin' => null,
            'horas' => 0,
        ]
    ];
    public $tramos = [
        ['hasta' => '', 'monto' => '']
    ];
    public $trabajadores = [];
    public $codigo_grupo;
    public $grupos = [];
    public $estandarProduccion;
    public $unidades = 'kg'; // Default unit, can be changed based on labor
    public $actividadId;
    protected $listeners = ['crearActividadDiaria', 'cuadrillerosAgregadosAsistencia', 'editarActividadDiaria', 'storeTableDataGuardarActividadDiaria'];


    public function mount()
    {

        $this->fecha = now()->format('Y-m-d');
        $this->labores = ActividadesServicio::obtenerLabores();
        $this->laboresSeleccion = $this->labores->map(function ($labor) {
            return [
                'id' => $labor->id,
                'name' => "{$labor->codigo} - {$labor->nombre_labor}",
            ];
        })->toArray();
        $this->buscarTrabajadores();

    }
    public function buscarTrabajadores()
    {

        try {
            $this->trabajadores = CuadrilleroServicio::obtenerTrabajadoresXDia($this->fecha);
        } catch (\Throwable $th) {
            $this->trabajadores = [];
        }
    }
    public function updatedLaborSeleccionada($codigoLabor)
    {

        $labor = ActividadesServicio::obtenerEstandarProduccion($codigoLabor);
        $this->tramos = $labor['tramos_bonificacion'];
        $this->estandarProduccion = $labor['estandar_produccion'];
        $this->unidades = $labor['unidades'];
    }
    public function crearActividadDiaria($fecha = null)
    {
        $this->reset(['actividadId', 'campoSeleccionado', 'laborSeleccionada', 'horarios_actividad', 'estandarProduccion', 'unidades']);
        $this->fecha = $fecha ?? now()->format('Y-m-d');
        $this->cuadrillerosAgregadosAsistencia();
        $this->mostrarFormularioActividadDiaria = true;
    }
    public function editarActividadDiaria($actividadId = null)
    {

        if ($actividadId) {

            $actividad = Actividad::find($actividadId);
            if ($actividad) {
                $this->actividadId = $actividadId;
                $this->tramos = $actividad->tramos_bonificacion ?? [['hasta' => '', 'monto' => '']];
                $this->fecha = $actividad->fecha;
                $this->campoSeleccionado = $actividad->campo;
                $this->laborSeleccionada = $actividad->labor_id;
                $this->horarios_actividad = $actividad->horarios ?? [
                    [
                        'inicio' => null,
                        'fin' => null,
                        'horas' => 0,
                    ]
                ];
                $this->estandarProduccion = $actividad->estandar_produccion;
                $this->unidades = $actividad->unidades;

            }
        }
        $this->cuadrillerosAgregadosAsistencia();
        $this->mostrarFormularioActividadDiaria = true;
    }
    public function storeTableDataGuardarActividadDiaria($datos)
    {
        $this->validate([
            'campoSeleccionado' => 'required|exists:campos,nombre',
            'fecha' => 'required|date',
            'laborSeleccionada' => 'required',
        ]);

        try {
            $totalHoras = collect($this->horarios_actividad)
                ->sum(function ($item) {
                    return $item['horas'] ?? 0;
                });

            $dataActividad = [
                'fecha' => $this->fecha,
                'campo' => $this->campoSeleccionado,
                'labor_id' => $this->laborSeleccionada,
                'horarios' => $this->horarios_actividad,
                'tramos_bonificacion' => $this->tramos,
                'estandar_produccion' => $this->estandarProduccion,
                'unidades' => $this->unidades,
                'total_horas' => $totalHoras,
                'created_by' => Auth::id(),
            ];
            $dataCuadrilleros = $datos;
            $actividadId = $this->actividadId;
            ActividadServicio::registrarActividadCuadrilla($dataActividad, $dataCuadrilleros, $actividadId);

            $cuaAsistenciaSemanal = CuadrilleroServicio::buscarSemana($dataActividad['fecha']);
            if ($cuaAsistenciaSemanal) {
                $cuaAsistenciaSemanal->contabilizarHoras();
            }
            
            $this->dispatch('cuadrillerosAgregadosAsistencia');
            $this->alert('success', 'Actividad registrada correctamente');
        } catch (\Throwable $th) {
            $this->alert('error' . $th->getMessage());
        }
    }
    public function cuadrillerosAgregadosAsistencia()
    {
        $nuevosTrabajadores = CuadrilleroServicio::obtenerTrabajadoresXDia($this->fecha, $this->actividadId);
        $this->dispatch('actualizarTablaCuadrilleros', $nuevosTrabajadores);
    }
    public function agregarCuadrilleros()
    {

        try {
            $cuadrilla_asistencia_id = CuadrilleroServicio::buscarSemana($this->fecha)->id;
            $this->dispatch('agregarCuadrilleros', $cuadrilla_asistencia_id);
        } catch (\Throwable $th) {
            $this->alert('error', 'Posiblemente no existe semana creada elija otra fecha o cree una semana.');
        }
    }

    public function render()
    {
        return view('livewire.actividades-diarias.actividades-diarias-form-component');
    }
}
