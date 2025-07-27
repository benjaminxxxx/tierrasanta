<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadOrdenSemanal;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\CuadrillaServicio;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Exception;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalComponent extends Component
{
    use LivewireAlert;

    public $fechaInicioSemana;
    public $anio, $mes, $semanaNumero;
    public $ocurrioModificaciones = false;
    public $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];
    public $reporteSemanal = [];
    public $headers = [];
    public $totalDias = 0;
    public $gruposDisponibles = [];
    public $colorPorGrupo;
    public $cuadrilleros = [];
    public $diasSemana = [];
    public $mostrarFormularioCostoHora = false;
    public $cuadrillerosCostosPersonalizados = [];

    #region Agregar Cuadrillero a semana
    public $mostrarAgregarCuadrillero = false;
    public $search;
    public $results = [];
    public $cuadrillerosAgregados = [];
    public $fecha;
    public $grupos = [];
    public $codigo_grupo;
    public $listaCuadrilleros = [];
    protected $listeners = ['grupoRegistrado','costosSemanalesModificados'];
    #endregion
    public function mount()
    {
        $this->gruposDisponibles = CuaGrupo::pluck('codigo')->toArray();
        $this->colorPorGrupo = CuaGrupo::pluck('color', 'codigo')->toArray();
        // Revisar si hay sesión guardada
        $this->fechaInicioSemana = Session::get('cuadrilla_fecha_inicio_semana');

        if (!$this->fechaInicioSemana) {
            // Por defecto, hoy → Lunes de esta semana
            $this->fechaInicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
            Session::put('cuadrilla_fecha_inicio_semana', $this->fechaInicioSemana);
        }
        $this->calcularAnioMes();
        $this->obtenerReporteSemanal(false);
        $this->cuadrilleros = Cuadrillero::where('estado', true)->pluck('nombres')->toArray();

        #region Agregar Cuadrillero a semana
        $this->grupos = CuaGrupo::where('estado', true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $this->grupos->first()->codigo;
        }
        $this->listaCuadrilleros = Cuadrillero::where('estado', true)
            ->select('id', 'nombres', 'dni')
            ->orderBy('nombres')
            ->get()
            ->toArray();
        #endregion
    }

    #region Panel principal
    public function costosSemanalesModificados(){
        $this->obtenerReporteSemanal();
    }

    public function abrirPrecioPersonalizado($cuadrilleros)
    {
        try {
            //Buscar al menos un registro con cuadrillero_id siendo null
            $existeRegistroNuevo = collect($cuadrilleros)->some('cuadrillero_id', null);
            if ($existeRegistroNuevo) {
                throw new Exception("Guarde primero los registros, clic en Actualizar horas");
            }

            if ($this->ocurrioModificaciones) {
                throw new Exception("Guarde primero los registros, clic en Actualizar horas");
            }
            $inicio = $this->semana->inicio;
            $fin = $this->semana->fin;

            $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
                ->whereNotNull('costo_personalizado_dia')
                ->get(['cuadrillero_id', 'fecha', 'costo_personalizado_dia']);

            $diasSemana = [];

            // Inicializar las fechas de la semana vacías
            $periodo = CarbonPeriod::create($inicio, $fin);
            foreach ($periodo as $date) {
                $fechaStr = $date->toDateString();
                $diasSemana[] = $fechaStr;
            }

            $registroCuadrilla = [];

            foreach ($cuadrilleros as $cuadrilla) {
                $indiceCuadrilla = $cuadrilla['cuadrillero_id'];
                $registroCuadrilla[$indiceCuadrilla] = [
                    'cuadrillero_id' => $cuadrilla['cuadrillero_id'],
                    'cuadrillero_nombres' => $cuadrilla['cuadrillero_nombres'],
                ];
                foreach ($periodo as $key => $date) {
                    $fechaStr = $date->toDateString();
                    $costoPersonalizado = $registros->first(function ($registro) use ($indiceCuadrilla, $fechaStr) {
                        return $registro->cuadrillero_id === $indiceCuadrilla && $registro->fecha->toDateString() === $fechaStr;
                    });

                    $registroCuadrilla[$indiceCuadrilla]['costos'][$key] = $costoPersonalizado?->costo_personalizado_dia;
                }
            }

            $this->diasSemana = $diasSemana;
            $this->cuadrillerosCostosPersonalizados = $registroCuadrilla;
            $this->mostrarFormularioCostoHora = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registrarCostoPersonalizado()
    {
        try {
            DB::beginTransaction();

            foreach ($this->cuadrillerosCostosPersonalizados as $cuadrilla) {
                $cuadrilleroId = $cuadrilla['cuadrillero_id'];

                foreach ($cuadrilla['costos'] as $index => $costo) {
                    $fecha = $this->diasSemana[$index];

                    if (!is_null($costo)) {

                        CuadRegistroDiario::updateOrCreate(
                            [
                                'cuadrillero_id' => $cuadrilleroId,
                                'fecha' => $fecha,
                            ],
                            [
                                'costo_personalizado_dia' => $costo,
                            ]
                        );
                    }
                }
            }

            CuadrilleroServicio::calcularCostosCuadrilla($this->semana->inicio, $this->semana->fin);

            DB::commit();
            $this->obtenerReporteSemanal();
            $this->alert('success', 'Costos personalizados actualizados correctamente');
            $this->mostrarFormularioCostoHora = false;

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->alert('error', 'Error al guardar: ' . $th->getMessage());
        }
    }

    public function obtenerReporteSemanal($dispatch = true)
    {
        $fechaInicio = $this->semana->inicio; //d/m/Y
        $fechaFin = $this->semana->fin;//d/m/Y
        $reporte = CuadrilleroServicio::obtenerHandsontableReporte($fechaInicio, $fechaFin);
        $this->reporteSemanal = $reporte['data'];
        $this->headers = $reporte['headers'];
        $this->totalDias = $reporte['total_dias'];
        if ($dispatch) {
            $this->dispatch('actualizarTablaReporteSemanal', $this->reporteSemanal, $this->totalDias, $this->headers);
        }
    }

    public function semanaAnterior()
    {
        $this->fechaInicioSemana = Carbon::parse($this->fechaInicioSemana)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->toDateString();

        $this->calcularAnioMes();
        $this->obtenerReporteSemanal();

        Session::put('cuadrilla_fecha_inicio_semana', $this->fechaInicioSemana);
    }

    public function siguienteSemana()
    {
        $this->fechaInicioSemana = Carbon::parse($this->fechaInicioSemana)
            ->addWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->toDateString();

        $this->calcularAnioMes();
        $this->obtenerReporteSemanal();

        Session::put('cuadrilla_fecha_inicio_semana', $this->fechaInicioSemana);
    }
    public function calcularAnioMes()
    {
        if ($this->fechaInicioSemana) {
            //asignar el mes y año de la semana seleccionada
            $this->anio = Carbon::parse($this->fechaInicioSemana)->format('Y');
            $this->mes = (int) Carbon::parse($this->fechaInicioSemana)->format('m');
        }
    }
    public function updatedAnio()
    {
        $this->seleccionarSemana();
    }
    public function updatedMes()
    {
        $this->seleccionarSemana();
    }
    public function updatedSemanaNumero()
    {
        $this->seleccionarSemana();
    }
    public function updatedCodigoGrupo()
    {
        $this->obtenerCuadrillerosAgregados();
    }
    public function seleccionarSemana()
    {
        // Si no llegan, usar valores por defecto
        $anio = (int) ($this->anio ?? now()->year);
        $mes = (int) ($this->mes ?? 1);
        $semanaNumero = (int) ($this->semanaNumero ?? 1);

        // Validar rangos tú mismo, si quieres
        if ($anio < 2000) {
            $anio = now()->year;
        }
        if ($mes < 1 || $mes > 12) {
            $mes = 1;
        }
        if ($semanaNumero < 1 || $semanaNumero > 5) {
            $semanaNumero = 1;
        }

        $fecha = Carbon::create($anio, $mes, 1)->startOfMonth();

        if ($fecha->dayOfWeek !== Carbon::MONDAY) {
            $fecha->next(Carbon::MONDAY);
        }

        $fecha->addWeeks($semanaNumero - 1);

        $this->fechaInicioSemana = $fecha->toDateString();
        $this->mes = (int) $fecha->format('m');
        $this->anio = (int) $fecha->format('Y');
        $this->obtenerReporteSemanal();
        Session::put('cuadrilla_fecha_inicio_semana', $this->fechaInicioSemana);
    }

    public function getSemanaProperty()
    {
        $inicio = Carbon::parse($this->fechaInicioSemana)->startOfWeek(Carbon::MONDAY);
        $fin = (clone $inicio)->endOfWeek(Carbon::SUNDAY);

        return (object) [
            'inicio' => $inicio->toDateString(),
            'fin' => $fin->toDateString(),
        ];
    }

    public function storeTableDataGuardarHoras($datos)
    {
        try {

            $fechaInicio = $this->semana->inicio;
            $fechaFin = $this->semana->fin;
            CuadrilleroServicio::guardarReporteSemanal($fechaInicio, $fechaFin, $datos);
            CuadrilleroServicio::calcularCostosCuadrilla($fechaInicio, $fechaFin);
            $this->obtenerReporteSemanal();
            $this->alert('success', 'Información actualizada');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    #endregion

    #region Agregar Cuadrillero a semana
    public function registrarComoNuevo()
    {
        try {
            $data = [
                'nombres' => mb_strtoupper(trim($this->search)),
                'dni' => null,
                'codigo_grupo' => $this->codigo_grupo,
            ];
            $cuadrillero = CuadrilleroServicio::guardarCuadrillero($data);
            if ($cuadrillero) {
                $this->cuadrillerosAgregados[] = [
                    'id' => $cuadrillero->id,
                    'nombres' => $cuadrillero->nombres,
                ];
                $this->search = null;
            }
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function obtenerCuadrillerosAgregados()
    {
        if (!$this->codigo_grupo) {
            $this->cuadrillerosAgregados = [];
            return;
        }
        $this->cuadrillerosAgregados = CuadOrdenSemanal::whereDate('fecha_inicio', $this->fechaInicioSemana)
            ->with(['cuadrillero'])
            ->where('codigo_grupo', $this->codigo_grupo)
            ->orderBy('orden')
            ->get(['cuadrillero_id', 'fecha_inicio', 'orden'])
            ->map(function ($cuadOrdenSemanal) {
                return [
                    'id' => $cuadOrdenSemanal->cuadrillero_id,
                    'nombres' => $cuadOrdenSemanal->cuadrillero->nombres
                ];
            })
            ->toArray();
    }
    public function agregarListaAgregada()
    {
        try {
            if (empty($this->cuadrillerosAgregados)) {
                throw new Exception("No ha agregado ningún cuadrillero");
            }
            if (!$this->codigo_grupo) {
                throw new Exception("No ha elegido ningún grupo");
            }
            $fechaInicio = $this->fechaInicioSemana;
            $rows = [];
            foreach ($this->cuadrillerosAgregados as $cuadrillero) {

                $rows[] = [
                    'cuadrillero_nombres' => $cuadrillero['nombres'],
                    'cuadrillero_id' => $cuadrillero['id']
                ];
            }

            $lista = CuadrilleroServicio::registrarOrdenSemanal($fechaInicio, $this->codigo_grupo, $rows);
            $this->alert('success', "Registros agregados");
            $this->mostrarAgregarCuadrillero = false;
            $this->resetForm();
            $this->obtenerReporteSemanal();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->search = null;
        $this->results = [];
        $this->cuadrillerosAgregados = [];
        $this->fecha = null;
        $this->codigo_grupo = null;
        $this->obtenerCuadrillerosAgregados();
    }
    public function cuadrilleroRegistrado($cuadrillero)
    {
        $this->agregarCuadrillero($cuadrillero['id']);
    }
    public function cuadrilleroRegistradoDeEmpleados($cuadrilleros)
    {
        if (!is_array($cuadrilleros)) {
            return;
        }
        foreach ($cuadrilleros as $idCuadrillero) {
            $this->agregarCuadrillero($idCuadrillero);
        }

    }
    public function grupoRegistrado($grupo)
    {
        $this->grupos = CuaGrupo::where('estado', true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $grupo['codigo'];
        }
        $this->obtenerCuadrillerosAgregados();
    }
    public function agregarCuadrillerosEnSemana()
    {
        $this->resetForm();
        $this->mostrarAgregarCuadrillero = true;
    }
    #endregion
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-component', [
            'semana' => $this->semana,
        ]);
    }
}
