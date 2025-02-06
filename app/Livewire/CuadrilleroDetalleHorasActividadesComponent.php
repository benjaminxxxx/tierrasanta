<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupoPrecios;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuadrillaHora;
use App\Models\CuadrilleroActividad;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrilleroDetalleHorasActividadesComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cuadrilleros = [];
    public $semana;
    public $diasSemana = [];
    public $actividadesPorDia = [];
    protected $listeners = ['verDetalleHoras'];
    public function verDetalleHoras($cuadrilleros)
    {

        $this->cuadrilleros = $cuadrilleros;
        $this->semana = null; //Importante resetear el valor por si se cambia de semana 

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
                //todos los cuadrilleros estan dentro de la misma semana, asi que buscar una sola vez
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

                    $actividadesEnFecha = $this->semana->actividades->whereDate('fecha', $fecha)
                    ->with(['recogidas','labores'])
                    ->get()
                    ->toArray();
                    if ($actividadesEnFecha) {
                        $this->actividadesPorDia[$fecha->day] = $actividadesEnFecha;
                    }
                    /*
                     0 => array:10 [▼
      "id" => 5
      "fecha" => "2024-12-02"
      "campo" => "1"
      "labor_id" => 54
      "horas_trabajadas" => "4.00"
      "labor_valoracion_id" => null
      "created_at" => "2025-01-29T02:14:54.000000Z"
      "updated_at" => "2025-01-29T02:14:54.000000Z"
      "recogidas" => []
      "labores" => array:6 [▼
        "id" => 54
        "nombre_labor" => "Aplicación sanitaria (mochila)"
        "created_at" => null
        "updated_at" => null
        "bono" => 0
        "estado" => 1
      ]
    ]
                    */

                    $cuadrillaHora = CuadrillaHora::whereDate('fecha', $fecha)->where('cua_asi_sem_cua_id', $cuadrillero['cua_asi_sem_cua_id'])->first();
                    $horas = 0;
                    $horasContabilizadas = 0;
                    if ($cuadrillaHora) {
                        $horas = $cuadrillaHora->horas;
                        $horasContabilizadas = $cuadrillaHora->horas_contabilizadas;
                    }
                    $nombre = $arrEs[mb_strtoupper($fecha->isoFormat('dddd'))];

                    $actividadesCuadrillero = CuadrilleroActividad::where('cua_asi_sem_cua_id', $cuadrillero['cua_asi_sem_cua_id'])
                        ->whereHas('actividad', function ($query) use ($fecha) {
                            $query->where('fecha', $fecha);
                        })
                        ->with(['actividad', 'recogidas'])
                        ->get()
                        ->keyBy('actividad_id');

                    $this->diasSemana[$fecha->day]['dia'] = $nombre;
                    $this->diasSemana[$fecha->day]['fecha'] = $fechaStr;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['horas'] = $horas;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['horas_contabilizadas'] = $horasContabilizadas;
                    $this->diasSemana[$fecha->day]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['detalle'] = $actividadesCuadrillero->toArray();
                    /*
                     detalles
                     array(9) { 
                     ["id"]=> int(16) ["cua_asi_sem_cua_id"]=> int(34) 
                     ["actividad_id"]=> int(7) 
                     ["total_bono"]=> string(4) "6.50" 
                     ["total_costo"]=> string(5) "17.50" 
                     ["created_at"]=> string(27) "2025-01-29T02:23:51.000000Z" 
                     ["updated_at"]=> string(27) "2025-02-06T00:26:51.000000Z" 
                     ["actividad"]=> array(8) { 
                        ["id"]=> int(7) 
                        ["fecha"]=> string(10) "2024-12-03" 
                        ["campo"]=> string(3) "1-1" 
                        ["labor_id"]=> int(149) 
                        ["horas_trabajadas"]=> string(4) "4.00" ["labor_valoracion_id"]=> int(4) ["created_at"]=> string(27) "2025-01-29T02:22:22.000000Z" ["updated_at"]=> string(27) "2025-01-29T02:22:22.000000Z" } ["recogidas"]=> array(1) { [0]=> array(7) { ["id"]=> int(1) ["cuadrillero_actividad_id"]=> int(16) ["recogida_id"]=> int(4) ["kg_logrados"]=> string(5) "15.00" ["bono"]=> string(4) "6.50" ["created_at"]=> string(27) "2025-01-29T02:23:51.000000Z" ["updated_at"]=> string(27) "2025-01-29T02:25:35.000000Z" } } } array(9) { ["id"]=> int(19) ["cua_asi_sem_cua_id"]=> int(34) ["actividad_id"]=> int(8) ["total_bono"]=> string(5) "11.25" ["total_costo"]=> string(5) "52.50" ["created_at"]=> string(27) "2025-01-29T02:29:50.000000Z" ["updated_at"]=> string(27) "2025-02-06T00:26:51.000000Z" ["actividad"]=> array(8) { ["id"]=> int(8) ["fecha"]=> string(10) "2024-12-03" ["campo"]=> string(4) "10-1" ["labor_id"]=> int(16) ["horas_trabajadas"]=> string(4) "6.00" ["labor_valoracion_id"]=> int(5) ["created_at"]=> string(27) "2025-01-29T02:29:13.000000Z" ["updated_at"]=> string(27) "2025-01-29T02:29:13.000000Z" } ["recogidas"]=> array(2) { [0]=> array(7) { ["id"]=> int(5) ["cuadrillero_actividad_id"]=> int(19) ["recogida_id"]=> int(7) ["kg_logrados"]=> string(4) "5.00" ["bono"]=> string(4) "4.50" ["created_at"]=> string(27) "2025-01-29T02:29:50.000000Z" ["updated_at"]=> string(27) "2025-01-29T02:30:01.000000Z" } [1]=> array(7) { ["id"]=> int(10) ["cuadrillero_actividad_id"]=> int(19) ["recogida_id"]=> int(6) ["kg_logrados"]=> string(4) "3.00" ["bono"]=> string(4) "6.75" ["created_at"]=> string(27) "2025-01-29T02:30:01.000000Z" ["updated_at"]=> string(27) "2025-01-29T02:30:01.000000Z" } } }
                     */
                }

            }
        }
        $this->mostrarFormulario = true;
    }
    public function render()
    {
        return view('livewire.cuadrillero-detalle-horas-actividades-component');
    }
}
