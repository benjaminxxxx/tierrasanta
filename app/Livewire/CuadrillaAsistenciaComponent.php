<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanal;
use App\Services\CuadrillaAsistenciaSemanalServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
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
        $this->currentSemana = Session::get('currentSemana');
        

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
            Session::put('currentSemana',$this->currentSemana);
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

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?, se van a eliminar las actividades realizadas en este rango de fechas', [
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
        $cuadrillaAsistenciaSemanalId = $data['semanaId'];
        /****************************20250123_CORRECCION_AL_ELIMINAR_SEMANA_COMPLETA */
        try {
            
            CuadrillaAsistenciaSemanalServicio::eliminarSemana($cuadrillaAsistenciaSemanalId);
            $this->currentSemana = null;
            Session::forget('currentSemana');
            $this->CuaAsistenciaSemanal = null;
            $this->revisarSemana();
            $this->alert('success', 'Registro Eliminado Correctamente.');

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al eliminar el registro semanal de cuadrilleros.');
        }
        /****************************20250123_CORRECCION_AL_ELIMINAR_SEMANA_COMPLETA */
    }
    public function fechaAnterior()
    {
        $this->currentSemana = $this->haySemanaAnterior;
        Session::put('currentSemana',$this->currentSemana);
        $this->revisarSemana();
    }
    public function fechaPosterior()
    {
        $this->currentSemana = $this->haySemanaPosterior;
        Session::put('currentSemana',$this->currentSemana);
        $this->revisarSemana();
    }
    public function seleccionarSemana($currentSemana)
    {
        $this->currentSemana = $currentSemana;
        Session::put('currentSemana',$this->currentSemana);
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
            $query->whereYear('fecha_inicio', $this->busquedaAnio);

            if ($this->busquedaMes) {
                $query->whereMonth('fecha_inicio', $this->busquedaMes);
            }
            
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
        return view('livewire.cuadrilla-asistencia-component');
    }
}
