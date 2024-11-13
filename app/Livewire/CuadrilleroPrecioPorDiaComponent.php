<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuaAsistenciaSemanalGrupoPrecios;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class CuadrilleroPrecioPorDiaComponent extends Component
{
    public $mostrarFormulario = false;
    public $cuadrilleros = [];
    public $semana;
    public $diasSemana = [];

    protected $listeners = ['customizarMontosPorDia'];
    public function customizarMontosPorDia($cuadrilleros)
    {

        $this->cuadrilleros = $cuadrilleros;
        
        $arrEs = [
            'MONDAY' => 'LUN',
            'TUESDAY' => 'MAR',
            'WEDNESDAY' => 'MIE',
            'THURSDAY' => 'JUE',
            'FRIDAY' => 'VIE',
            'SATURDAY' => 'SAB',
            'SUNDAY' => 'DOM'
        ];

        foreach ($cuadrilleros as $indice => $cuadrillero) {
            $grupoId = $cuadrillero['cua_asi_sem_gru_id'];
            
            $grupoSemanal = CuaAsistenciaSemanalGrupo::find($grupoId);

            if (!$this->semana) {    
                if ($grupoSemanal) {
                    $this->semana = $grupoSemanal->asistenciaSemanal()->first();
                }
            }
            if ($this->semana && $grupoSemanal) {
                $fechaInicio = Carbon::parse($this->semana->fecha_inicio);
                $fechaFin = Carbon::parse($this->semana->fecha_fin);
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);              

                foreach ($periodo as $fecha) {
                    
                    $costoDia = $grupoSemanal->costo_dia;
                    $fechaStr = $fecha->toDateString();
                    $preciosPersonalizado = CuaAsistenciaSemanalGrupoPrecios::whereNull('cuadrillero_id')
                    ->whereDate('fecha', $fecha)
                    ->where('cua_asistencia_semanal_grupo_id',$grupoId)
                    ->first();

                    if ($preciosPersonalizado) {
                        $costoDia = $preciosPersonalizado->costo_dia;
                    }
                    $nombre = $arrEs[mb_strtoupper($fecha->isoFormat('dddd'))];
                    $this->diasSemana[$fecha->day]['dia'] = $nombre;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']] = $costoDia;
                }
                
            }

        }
        $this->mostrarFormulario = true;
    }
    public function updatedDiasSemana($value,$key){
        //dd($value . ' ... ' . $key);
        //"100 ... 14.cuadrillero.4"
    }
    public function render()
    {
        return view('livewire.cuadrillero-precio-por-dia-component');
    }
}
