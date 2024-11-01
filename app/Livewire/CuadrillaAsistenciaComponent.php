<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaAsistenciaComponent extends Component
{
    use LivewireAlert;
    public $cuadrilla;
    public $cuadrilleros;
    public $grupos;
    public $fechas = [];
    public $cuadrillerosPorGrupo;
    public $CuaAsistenciaSemanal;
    public $haySemanaAnterior;
    public $haySemanaPosterior;
    public $currentSemana;
    public $aniosDisponibles;
    public $estaBuscadorAbierto = false;
    public $busquedaAnio = null;
    public $busquedaMes = null;
    public $semanas = [];
    protected $listeners = ['NuevaCuadrilla', 'confirmarEliminar'];
    public $diasSemana = [
        'Sunday' => 'Domingo',
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado'
    ];
    public function mount()
    {
        $this->revisarSemana();
    }
    public function NuevaCuadrilla()
    {
        $this->revisarSemana();
    }
    public function revisarSemana()
    {

        if ($this->currentSemana) {
            $this->CuaAsistenciaSemanal = CuaAsistenciaSemanal::find($this->currentSemana);
        } else {
            $fecha = Carbon::now()->format('Y-m-d');
            $this->CuaAsistenciaSemanal = CuaAsistenciaSemanal::whereDate('fecha_inicio', '<=', $fecha)
                ->whereDate('fecha_fin', '>=', $fecha)
                ->first();
        }


        if ($this->CuaAsistenciaSemanal) {
            
            $this->currentSemana = $this->CuaAsistenciaSemanal->id;
            // Buscar el id de la semana anterior (si existe)
            $anterior = CuaAsistenciaSemanal::whereDate('fecha_fin', '<', $this->CuaAsistenciaSemanal->fecha_inicio)
                ->orderBy('fecha_fin', 'desc')
                ->first();
            $this->haySemanaAnterior = $anterior ? $anterior->id : null;

            // Buscar el id de la semana posterior (si existe)
            $posterior = CuaAsistenciaSemanal::whereDate('fecha_inicio', '>', $this->CuaAsistenciaSemanal->fecha_fin)
                ->orderBy('fecha_inicio', 'asc')
                ->first();
            $this->haySemanaPosterior = $posterior ? $posterior->id : null;
            $this->grupos = $this->CuaAsistenciaSemanal->grupos->pluck('id')->toArray();
            
        } else {
            $posterior = CuaAsistenciaSemanal::whereDate('fecha_inicio', '>', Carbon::now()->format('Y-m-d'))
                ->orderBy('fecha_inicio', 'asc')
                ->first();
            $this->haySemanaPosterior = $posterior ? $posterior->id : null;

            // Buscar también la semana anterior a la fecha actual
            $anterior = CuaAsistenciaSemanal::whereDate('fecha_fin', '<', Carbon::now()->format('Y-m-d'))
                ->orderBy('fecha_fin', 'desc')
                ->first();
            $this->haySemanaAnterior = $anterior ? $anterior->id : null;
        }
    }

    public function confirmarEliminarRegistroSemanal()
    {
        if (!$this->currentSemana)
            return;

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'semanaId' => $this->currentSemana,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $semanaId = $data['semanaId'];
        CuaAsistenciaSemanal::find($semanaId)->delete();        
        $this->currentSemana = null;
        $this->CuaAsistenciaSemanal = null;
        $this->revisarSemana();
        $this->alert('success', 'Registro Eliminado Correctamente.');
    }
    public function fechaAnterior()
    {
        $this->currentSemana = $this->haySemanaAnterior;
        $this->revisarSemana();
    }
    public function fechaPosterior()
    {
        $this->currentSemana = $this->haySemanaPosterior;
        $this->revisarSemana();
    }
    public function seleccionarSemana($currentSemana)
    {
        $this->currentSemana = $currentSemana;
        $this->estaBuscadorAbierto = false;
        $this->aniosDisponibles = null;
        $this->busquedaAnio = null;
        $this->semanas = [];
        $this->revisarSemana();
    }
    public function buscarSemana()
    {
        $this->estaBuscadorAbierto = true;
        $this->aniosDisponibles = CuaAsistenciaSemanal::selectRaw('YEAR(fecha_inicio) as anio')
            ->distinct()
            ->pluck('anio')
            ->toArray();
        $this->semanas = [];
    }
    public function filtrarSemanas()
    {
        if ($this->busquedaAnio) {
            $query = CuaAsistenciaSemanal::query();

            // Filtrar por año
            $query->whereYear('fecha_inicio', $this->busquedaAnio);

            // Filtrar por mes si se seleccionó
            if ($this->busquedaMes) {
                $query->whereMonth('fecha_inicio', $this->busquedaMes);
            }

            // Obtener semanas filtradas
            $this->semanas = $query->orderBy('fecha_inicio', 'desc')->get();
        } else {
            $this->semanas = [];
        }
    }
    public function cerrarBuscador()
    {
        $this->estaBuscadorAbierto = false;
    }
    public function render()
    {
        /*
        $this->cuadrilla = CuaAsistenciaSemanal::orderBy('fecha_fin', 'desc')->first();

        if ($this->cuadrilla) {
            $inicio = Carbon::parse($this->cuadrilla->fecha_inicio);
            $fin = Carbon::parse($this->cuadrilla->fecha_fin);
            $this->fechas = [];
            for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
                $nombreDiaIngles = $date->format('l'); // Nombre del día en inglés
                $nombreDiaEspañol = $this->diasSemana[$nombreDiaIngles]; // Convertir al español

                $this->fechas[] = [
                    'dia_numero' => $date->format('d'),
                    'dia_nombre' => $nombreDiaEspañol
                ];
            }

            // Obtener grupos ordenados por modalidad de pago
            $this->grupos = $this->cuadrilla->grupos()->orderBy('modalidad_pago')->get();

            // Obtener todos los cuadrilleros relacionados con la cuadrilla
            $this->cuadrilleros = $this->cuadrilla->cuadrilleros()->orderBy('codigo_grupo')->get();

            // Agrupar cuadrilleros por código de grupo
            $this->cuadrillerosPorGrupo = $this->cuadrilleros->groupBy('codigo_grupo')->all();
        }*/

        return view('livewire.cuadrilla-asistencia-component');
    }
}
