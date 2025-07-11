<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalComponent extends Component
{
    use LivewireAlert;

    public $fechaInicioSemana;
    public $anio, $mes, $semanaNumero;
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
    public function updatedSemana()
    {
        $this->seleccionarSemana();
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
    public function asignarCostos()
    {
        //asignar 7 dias a la fecha de inicio
        $inicio = $this->fechaInicioSemana;
        $fin = Carbon::parse($this->fechaInicioSemana)->copy()->addDays(7);
        $this->dispatch('asignarCostosPorFecha', $inicio, $fin);
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

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-component', [
            'semana' => $this->semana,
        ]);
    }
}
