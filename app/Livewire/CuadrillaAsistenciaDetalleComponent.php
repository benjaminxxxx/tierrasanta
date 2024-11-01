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
    protected $listeners = ['eliminarCuadrilleros', 'cuadrillerosAgregadosAsistencia', 'storeTableDataCuadrilla'];

    public function mount()
    {
        if ($this->cuaAsistenciaSemanalId) {
            $this->semana = CuaAsistenciaSemanal::find($this->cuaAsistenciaSemanalId);
            if ($this->semana) {
                $this->titulo = mb_strtoupper($this->semana->titulo);
            }
            $this->periodo = $this->generarDiasSemana($this->semana->fecha_inicio, $this->semana->fecha_fin);
            $this->obtenerCuadrilleros();
        }
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

        

        foreach ($data as $row) {
            // Obtener la información de la semana a partir de `cuadrillero_id`
            $asistenciaSemanalCuadrillero = CuaAsistenciaSemanalCuadrillero::find($row['cua_asi_sem_cua_id']);
            $grupo = CuaAsistenciaSemanalGrupo::find($row['cua_asi_sem_gru_id']);
            CuadrillaHora::where('cua_asi_sem_cua_id', $row['cua_asi_sem_cua_id'])->delete();

            if (!$asistenciaSemanalCuadrillero || !$grupo) {
                continue; // Saltar al siguiente si faltan datos
            }
            $monto = 0;
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

            $asistenciaSemanalCuadrillero->monto_recaudado = $monto;
            $asistenciaSemanalCuadrillero->save();
        }
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

            $this->cuadrilleros = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $this->cuaAsistenciaSemanalId)
        
                ->get()
                ->filter(function ($grupo) {
                    // Filtramos para que solo pasen los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia()->exists();
                })
                ->map(function ($grupo) use ($periodo, $fechaInicio, $fechaFin) {
                    // Ahora mapeamos solo los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia->map(function ($cuadrilleroDeAsistencia) use ($grupo, $periodo, $fechaInicio, $fechaFin) {

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
                            $cuadrilleroData[$diaKey] = $horasRegistradas->get($diaKey)->horas ?? ''; // 0 si no hay registro
                            $cuadrilleroData[$diaKey . '_monto'] = $horasRegistradas->get($diaKey)->costo_dia ?? ''; // 0 si no hay registro
                        }

                        return $cuadrilleroData;
                    });
                })
                ->flatten(1)
                ->sortBy(['codigo_grupo', 'nombres'])
                ->values();
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
