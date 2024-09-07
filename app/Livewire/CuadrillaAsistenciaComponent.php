<?php

namespace App\Livewire;

use App\Models\CuadrillaAsistencia;
use App\Models\CuadrillaAsistenciaGrupo;
use Carbon\Carbon;
use Livewire\Component;

class CuadrillaAsistenciaComponent extends Component
{
    public $cuadrilla;
    public $cuadrilleros;
    public $grupos;
    public $fechas = [];
    public $cuadrillerosPorGrupo;
    protected $listeners = ['NuevaCuadrilla' => '$refresh'];
    public $diasSemana = [
        'Sunday' => 'Domingo',
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado'
    ];

    public function render()
    {
        $this->cuadrilla = CuadrillaAsistencia::orderBy('fecha_fin', 'desc')->first();

        if ($this->cuadrilla) {
            $inicio = Carbon::parse($this->cuadrilla->fecha_inicio);
            $fin = Carbon::parse($this->cuadrilla->fecha_fin);
            $this->fechas = [];
            for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
                $nombreDiaIngles = $date->format('l'); // Nombre del día en inglés
                $nombreDiaEspañol = $this->diasSemana[$nombreDiaIngles]; // Convertir al español
                
                $this->fechas[] = [
                    'dia_numero'=>$date->format('d'),
                    'dia_nombre'=>$nombreDiaEspañol
                ];
            }

            // Obtener grupos ordenados por modalidad de pago
            $this->grupos = $this->cuadrilla->grupos()->orderBy('modalidad_pago')->get();
          
            // Obtener todos los cuadrilleros relacionados con la cuadrilla
            $this->cuadrilleros = $this->cuadrilla->cuadrilleros()->orderBy('codigo_grupo')->get();

            // Agrupar cuadrilleros por código de grupo
            $this->cuadrillerosPorGrupo = $this->cuadrilleros->groupBy('codigo_grupo')->all();

        }

        return view('livewire.cuadrilla-asistencia-component');
    }

}
