<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuadrillaHora;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CuadrillaAsistenciaDetalleComponent extends Component
{
    use LivewireAlert;
    public $cuaAsistenciaSemanalId;
    public $cuadrilleros;
    public $semana;
    public $periodo;
    public $titulo;
    public $gruposTotales;
    protected $listeners = ['eliminarCuadrilleros', 'cuadrillerosAgregadosAsistencia', 'storeTableDataCuadrilla'];

    public function mount()
    {
        if ($this->cuaAsistenciaSemanalId) {
            $this->obtenerSemana();
            $this->obtenerCuadrilleros();
        }
    }
    public function obtenerSemana(){
        $this->semana = CuaAsistenciaSemanal::find($this->cuaAsistenciaSemanalId);
        if ($this->semana) {
            $this->titulo = mb_strtoupper($this->semana->titulo);
            $this->gruposTotales = $this->semana->grupos()->get()->sortBy('gru_cua_cod');
        }
        $this->periodo = $this->generarDiasSemana($this->semana->fecha_inicio, $this->semana->fecha_fin);
    }
    public function storeTableDataCuadrilla($data)
    {

        if (!$this->semana) {
            return;
        }



        $fechaInicio = Carbon::parse($this->semana->fecha_inicio);
        $fechaFin = Carbon::parse($this->semana->fecha_fin);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $diasSemana = [];
        foreach ($periodo as $fecha) {
            $diasSemana[$fecha->day] = $fecha;
        }
        $costoPorGrupo = [];
        

        foreach ($data as $row) {

            if($row['codigo_grupo']=='TOTALES'){
                continue;
            }
            // Obtener la información de la semana a partir de `cuadrillero_id`
            $asistenciaSemanalCuadrillero = CuaAsistenciaSemanalCuadrillero::find($row['cua_asi_sem_cua_id']);
            $grupo = CuaAsistenciaSemanalGrupo::find($row['cua_asi_sem_gru_id']);
            CuadrillaHora::where('cua_asi_sem_cua_id', $row['cua_asi_sem_cua_id'])->delete();

            if (!$asistenciaSemanalCuadrillero || !$grupo) {
                continue; // Saltar al siguiente si faltan datos
            }
            $monto = 0;
            if(!array_key_exists($row['cua_asi_sem_gru_id'],$costoPorGrupo)){
                $costoPorGrupo[$row['cua_asi_sem_gru_id']] = 0;
            }
            
            // Recorrer cada clave de `row` para encontrar los días y horas
            foreach ($row as $key => $value) {

                if (str_ends_with($key, '_monto')) {
                    continue;
                }
                // Verificar si es un campo `dia_X`
                if (strpos($key, 'dia_') === 0 && !empty($value)) {
                    // Extraer el número del día
                    $diaNumero = (int)str_replace('dia_', '', $key);

                    if (array_key_exists($diaNumero, $diasSemana)) {
                        $fecha = $diasSemana[$diaNumero];
                        $subtotal = (float)$grupo->costo_hora * (float)$value;
                        $monto+= $subtotal;

                        CuadrillaHora::updateOrCreate(
                            [
                                'cua_asi_sem_cua_id' => $row['cua_asi_sem_cua_id'],
                                'fecha' => $fecha->format('Y-m-d')
                            ],
                            [
                                'horas' => (float)$value,
                                'costo_dia' => $subtotal,
                            ]
                        );
                    }
                }
            }
            $costoPorGrupo[$row['cua_asi_sem_gru_id']]+=$monto;
            $asistenciaSemanalCuadrillero->monto_recaudado = $monto;
            $asistenciaSemanalCuadrillero->save();
        }
      
        foreach($costoPorGrupo as $codigoGrupo => $montoTotal){
            CuaAsistenciaSemanalGrupo::find($codigoGrupo)->update(['total_costo'=>$montoTotal]);
        }

        
        $this->semana->total = array_sum($costoPorGrupo);
        $this->semana->save();
     
        $this->obtenerSemana();
        $this->obtenerCuadrilleros();
        $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);
        $this->alert('success', 'Datos de horas guardados exitosamente.');
    }
    public function obtenerCuadrilleros()
    {
        if ($this->cuaAsistenciaSemanalId) {

            $fechaInicio = Carbon::parse($this->semana->fecha_inicio);
            $fechaFin = Carbon::parse($this->semana->fecha_fin);
            $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

            $totalesDiarios = [];
            foreach ($periodo as $fecha) {
                $diaKey = 'dia_' . $fecha->day;
                $totalesDiarios[$diaKey] = ['horas' => 0, 'monto' => 0];
            }

            $this->cuadrilleros = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $this->cuaAsistenciaSemanalId)
        
                ->get()
                ->filter(function ($grupo) {
                    // Filtramos para que solo pasen los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia()->exists();
                })
                ->map(function ($grupo) use ($periodo, $fechaInicio, $fechaFin,&$totalesDiarios) {
                    // Ahora mapeamos solo los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia->map(function ($cuadrilleroDeAsistencia) use ($grupo, $periodo, $fechaInicio, $fechaFin,&$totalesDiarios) {

                        $cuadrilleroData = [
                            'cua_id' => $cuadrilleroDeAsistencia->cua_id,
                            'cua_asi_sem_cua_id' => $cuadrilleroDeAsistencia->id,
                            'cua_asi_sem_gru_id' => $grupo->id,
                            'dni' => $cuadrilleroDeAsistencia->cuadrillero->dni,
                            'color' => $grupo->grupo->color,
                            'codigo_grupo' => $grupo->gru_cua_cod,
                            'nombres' => $cuadrilleroDeAsistencia->cuadrillero->nombres,
                            'monto'=>$cuadrilleroDeAsistencia->monto_recaudado
                        ];

                        $horasRegistradas = CuadrillaHora::where('cua_asi_sem_cua_id', $cuadrilleroDeAsistencia->id)
                            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->get()
                            ->keyBy(function ($hora) {
                                return 'dia_' . Carbon::parse($hora->fecha)->day;
                            });

                        foreach ($periodo as $fecha) {
                            $diaKey = 'dia_' . $fecha->day;
                            $horas = $horasRegistradas->get($diaKey)->horas ?? 0;
                            $monto = $horasRegistradas->get($diaKey)->costo_dia ?? 0;
    
                            $cuadrilleroData[$diaKey] = $horas;
                            $cuadrilleroData[$diaKey . '_monto'] = $monto;
    
                            // Acumular en los totales diarios
                            $totalesDiarios[$diaKey]['horas'] += $horas;
                            $totalesDiarios[$diaKey]['monto'] += $monto;
                        }

                        return $cuadrilleroData;
                    });
                })
                ->flatten(1)
                ->sortBy(['codigo_grupo', 'nombres'])
                ->values();

                $totalesData = [
                    'cua_id' => '',
                    'cua_asi_sem_cua_id' => '',
                    'cua_asi_sem_gru_id' => '',
                    'dni' => '',
                    'color' => '',
                    'codigo_grupo' => 'TOTALES',
                    'nombres' => '',
                    'monto' => array_sum(array_column($totalesDiarios, 'monto'))
                ];
                
                // Agregar totales diarios por cada día
                foreach ($totalesDiarios as $diaKey => $totales) {
                    $totalesData[$diaKey] = $totales['horas'];
                    $totalesData[$diaKey . '_monto'] = round($totales['monto'],2);
                }
        
                $this->cuadrilleros[] = $totalesData;
        }
    }
    public function actualizarEstadoGrupoEnSemana($cuadrillaId,$valor){
        $grupoSemanal = $this->semana->grupos()->find($cuadrillaId);
        if($grupoSemanal){
            $grupoSemanal->estado_pago = $valor;
            $grupoSemanal->save();
            $this->alert('success','Estado modificado exitosamente');
        }
    }
    public function actualizarFechaGrupoEnSemana($cuadrillaId,$valor){
        $grupoSemanal = $this->semana->grupos()->find($cuadrillaId);
        if($grupoSemanal){
            $grupoSemanal->fecha_pagado = $valor;
            $grupoSemanal->save();
            $this->alert('success','Fecha modificada exitosamente');
        }
    }
    public function generarDiasSemana($inicio, $fin)
    {
        if ($inicio && $fin) {
            $inicio = Carbon::parse($inicio);
            $fin = Carbon::parse($fin);
            $periodo = CarbonPeriod::create($inicio, $fin);
            $diasSemana = [];
            foreach ($periodo as $fecha) {
                $diasSemana[] = [
                    'dia' => $fecha->day,
                    'nombre' => mb_strtoupper($fecha->locale('es')->isoFormat('dddd')),
                ];
            }
            return $diasSemana;
        }
        return [];
    }
    public function eliminarCuadrilleros($cuadrilleros)
    {

        foreach ($cuadrilleros as $cuadrillero) {

            CuaAsistenciaSemanalCuadrillero::where('cua_id', $cuadrillero['cua_id'])
                ->where('cua_asi_sem_gru_id', $cuadrillero['cua_asi_sem_gru_id'])
                ->delete();
        }
        $this->obtenerCuadrilleros();
        $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);
    }
    public function cuadrillerosAgregadosAsistencia()
    {
        $this->obtenerCuadrilleros();
        $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);
    }
    public function render()
    {
        return view('livewire.cuadrilla-asistencia-detalle-component');
    }
}
