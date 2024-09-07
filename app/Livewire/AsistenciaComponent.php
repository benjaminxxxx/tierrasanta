<?php

namespace App\Livewire;

use App\Models\Dia;
use App\Models\Empleado;
use Livewire\Component;
use Carbon\Carbon;

class AsistenciaComponent extends Component
{
    public $empleados;
    public $mes;
    public $meses;
    public $anio;
    public $dias;
    public function mount()
    {
        $this->empleados = Empleado::where('status', 'activo')
            ->orderBy('grupo_codigo', 'desc')
            ->orderBy('cargo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')->get();

        $this->meses = collect([
            ['value' => 1, 'label' => 'Enero'],
            ['value' => 2, 'label' => 'Febrero'],
            ['value' => 3, 'label' => 'Marzo'],
            ['value' => 4, 'label' => 'Abril'],
            ['value' => 5, 'label' => 'Mayo'],
            ['value' => 6, 'label' => 'Junio'],
            ['value' => 7, 'label' => 'Julio'],
            ['value' => 8, 'label' => 'Agosto'],
            ['value' => 9, 'label' => 'Septiembre'],
            ['value' => 10, 'label' => 'Octubre'],
            ['value' => 11, 'label' => 'Noviembre'],
            ['value' => 12, 'label' => 'Diciembre'],
        ])->filter(function ($month) {
            // Filtrar meses hasta el mes actual
            return $month['value'] <= Carbon::now()->month;
        });

        //$this->mes = Carbon::now()->month;
        $this->anio = Carbon::now()->year;

        $this->cargarDias();
    }
    public function updatedMes()
    {
        $this->cargarDias();
    }
    public function cargarDias()
    {
        if(!$this->mes){
            return;
        }
        $inicioMes = Carbon::create($this->anio, $this->mes, 1);
        $finMes = $inicioMes->copy()->endOfMonth();

        // Verifica si ya existen registros para el mes y año seleccionados
        $diasExistentes = Dia::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->count();

        // Si no existen registros, los crea
        if ($diasExistentes == 0) {
            while ($inicioMes <= $finMes) {
                Dia::create([
                    'dia' => $inicioMes->day,
                    'mes' => $inicioMes->month,
                    'anio' => $inicioMes->year,
                    'es_dia_no_laborable' => false, // Por defecto, no es feriado
                    'es_dia_domingo' => $inicioMes->isSunday(),
                    'observaciones' => null, // No hay observaciones por defecto
                ]);
                $inicioMes->addDay();
            }
        }

        // Carga los días del mes, incluyendo la información de la base de datos
        $this->dias = Dia::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->get()->toArray();
    }
    public function render()
    {
        return view('livewire.asistencia-component');
    }
}
