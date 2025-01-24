<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuaAsistenciaSemanalGrupoPrecios;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrilleroPrecioPorDiaComponent extends Component
{
    use LivewireAlert;
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
                    $costoReferencia = 'base';
                    $preciosPersonalizado = CuaAsistenciaSemanalGrupoPrecios::whereNull('cua_asi_sem_cua_id')
                        ->whereDate('fecha', $fecha)
                        ->where('cua_asistencia_semanal_grupo_id', $grupoId)
                        ->first();

                    if ($preciosPersonalizado) {
                        $costoDia = $preciosPersonalizado->costo_dia;
                        $costoReferencia = 'semana';
                    }

                    $preciosPersonalizadoPorCuadrillero = CuaAsistenciaSemanalGrupoPrecios::where('cua_asi_sem_cua_id', $cuadrillero['cua_asi_sem_cua_id'])
                        ->whereDate('fecha', $fecha)
                        ->where('cua_asistencia_semanal_grupo_id', $grupoId)
                        ->first();

                    if ($preciosPersonalizadoPorCuadrillero) {
                        $costoDia = $preciosPersonalizadoPorCuadrillero->costo_dia;
                        $costoReferencia = 'cuadrillero';
                    }

                    $nombre = $arrEs[mb_strtoupper($fecha->isoFormat('dddd'))];
                    $this->diasSemana[$fecha->day]['dia'] = $nombre;
                    $this->diasSemana[$fecha->day]['fecha'] = $fechaStr;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['costoDia'] = $costoDia;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['costoReferencia'] = $costoReferencia;
                }

            }

        }
        $this->mostrarFormulario = true;
    }
    public function updatedDiasSemana($costoDia, $key)
    {

        //"key devuelve 14.cuadrillero.4"
        try {
            if (!$this->semana) {
                return;
            }
            list($dia, $cuadrillero) = explode('.cuadrillero.', $key);

            $cuadrillero = (int) str_replace('.costoDia', '', $cuadrillero);
            $fecha = $this->diasSemana[$dia]['fecha'];
            $cuadrilleroObjeto = CuaAsistenciaSemanalCuadrillero::find($cuadrillero);


            if ($cuadrilleroObjeto) {

                CuaAsistenciaSemanalGrupoPrecios::where('cua_asistencia_semanal_grupo_id', $cuadrilleroObjeto->cua_asi_sem_gru_id)
                    ->where('cua_asi_sem_id', $this->semana->id)
                    ->whereDate('fecha', $fecha)
                    ->where('cua_asi_sem_cua_id', $cuadrillero)
                    ->delete();

                if (trim($costoDia) == "") {
                    $this->customizarMontosPorDia($this->cuadrilleros);
                    $this->dispatch('cuadrillerosAgregadosAsistencia');
                    $this->alert('success', 'Registro eliminado correctamente.');
                    return;
                }

                CuaAsistenciaSemanalGrupoPrecios::create([
                    'cua_asistencia_semanal_grupo_id' => $cuadrilleroObjeto->cua_asi_sem_gru_id,
                    'cua_asi_sem_id' => $this->semana->id,
                    'gru_cua_cod' => $cuadrilleroObjeto->asistenciaSemanalGrupo()->first()->gru_cua_cod,
                    'costo_dia' => (float) $costoDia,
                    'costo_hora' => (float) $costoDia / 8,
                    'fecha' => $fecha,
                    'cua_asi_sem_cua_id' => $cuadrillero,
                ]);
                $this->customizarMontosPorDia($this->cuadrilleros);
                $this->dispatch('cuadrillerosAgregadosAsistencia');
                return $this->alert('success', 'Registro actualizado correctamente.');
            }


        } catch (\Throwable $th) {
            return $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.cuadrillero-precio-por-dia-component');
    }
}
