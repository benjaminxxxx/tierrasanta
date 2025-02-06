<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuaAsistenciaSemanalGrupoPrecios;
use App\Models\CuadrillaHora;
use App\Services\CuadrillaServicio;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
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
    public $precios = [];
    public $observaciones = [];
    protected $listeners = ['eliminarCuadrilleros', 'cuadrillerosAgregadosAsistencia', 'guardarInformacionPlanillaHoras'];

    public function mount()
    {
        if ($this->cuaAsistenciaSemanalId) {
            $this->obtenerSemana();
            $this->obtenerCuadrilleros();
            $this->obtenerObservaciones();
        }
    }
    public function guardarInformacionPlanillaHoras($datos)
    {
        try {
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

            $preciosPersonalizados = CuaAsistenciaSemanalGrupoPrecios::whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->get()
                ->groupBy(function ($item) {
                    return $item->cua_asistencia_semanal_grupo_id . '_' . $item->fecha . '_' . $item->cua_asi_sem_cua_id;
                });

            foreach ($datos as $row) {

                if ($row['codigo_grupo'] == 'TOTALES') {
                    continue;
                }
                // Obtener la información de la semana a partir de `cuadrillero_id`
                $asistenciaSemanalCuadrillero = CuaAsistenciaSemanalCuadrillero::find($row['cua_asi_sem_cua_id']);

                if (!$asistenciaSemanalCuadrillero) {
                    break;
                }

                $grupo = $asistenciaSemanalCuadrillero->asistenciaSemanalGrupo;

                // Recorrer cada clave de `row` para encontrar los días y horas
                foreach ($row as $key => $totalHorasTrabajadas) {



                    if (str_ends_with($key, '_monto') || str_ends_with($key, '_contabilizado') || str_ends_with($key, '_bono')) {
                        continue;
                    }

                    if (strpos($key, 'dia_') === 0) {
                        $diaNumero = (int) str_replace('dia_', '', $key);
                        if (!array_key_exists($diaNumero, $diasSemana)) {
                            continue;
                        }

                        $fecha = $diasSemana[$diaNumero];
                        $fechaStr = $fecha->toDateString();
                        if (!empty($totalHorasTrabajadas)) {





                            $costoHora = (float) $grupo->costo_hora;

                            $personalizadoKey = $grupo->id . '_' . $fechaStr . '_';
                            $personalizadoCuadrillero = $grupo->id . '_' . $fechaStr . '_' . $row['cua_asi_sem_cua_id'];

                            if (isset($preciosPersonalizados[$personalizadoKey])) {
                                $personalizado = $preciosPersonalizados[$personalizadoKey]->first();
                                $costoHora = (float) $personalizado->costo_hora;
                            }
                            if (isset($preciosPersonalizados[$personalizadoCuadrillero])) {
                                $personalizado = $preciosPersonalizados[$personalizadoCuadrillero]->first();
                                $costoHora = (float) $personalizado->costo_hora;
                            }

                            $subtotal = $costoHora * (float) $totalHorasTrabajadas;

                            CuadrillaHora::updateOrCreate(
                                [
                                    'cua_asi_sem_cua_id' => $row['cua_asi_sem_cua_id'],
                                    'fecha' => $fecha->format('Y-m-d')
                                ],
                                [
                                    'horas' => (float) $totalHorasTrabajadas,
                                    'costo_dia' => $subtotal,
                                ]
                            );

                        } else {
                            CuadrillaHora::updateOrCreate(
                                [
                                    'cua_asi_sem_cua_id' => $row['cua_asi_sem_cua_id'],
                                    'fecha' => $fecha->format('Y-m-d')
                                ],
                                [
                                    'horas' => 0,
                                    'costo_dia' => 0,
                                ]
                            );

                        }

                    }
                }

            }

            $this->semana->actualizarTotales();
            $this->obtenerCuadrilleros();
            $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al registrar las horas.');
        }
    }
    public function obtenerObservaciones()
    {
        if (!$this->semana) {
            return;
        }
        $preciosPersonalizados = CuaAsistenciaSemanalGrupoPrecios::whereBetween('fecha', [$this->semana->fecha_inicio, $this->semana->fecha_fin])
            ->orderBy('cua_asi_sem_cua_id')
            ->orderBy('fecha')
            ->get();

        $this->observaciones = [];

        foreach ($preciosPersonalizados as $observacion) {

            if ($observacion->cua_asi_sem_cua_id) {
                //dd($observacion);
                $cuadrillero = $observacion->cuadrillero->cuadrillero->nombres;

                $this->observaciones[] = "El(la) cuadrillero(a) " . $cuadrillero . " tiene un monto personalizado para la fecha " . $observacion->fecha . " para el grupo " . $observacion->gru_cua_cod;
            } else {
                $this->observaciones[] = "Se agregó un monto personalizado para la fecha " . $observacion->fecha . " para el grupo " . $observacion->gru_cua_cod;
            }
        }
    }
    public function obtenerSemana()
    {
        $this->semana = CuaAsistenciaSemanal::find($this->cuaAsistenciaSemanalId);
        if ($this->semana) {
            $this->titulo = mb_strtoupper($this->semana->titulo);
            $this->gruposTotales = $this->semana->grupos()->get()->sortBy('gru_cua_cod');

        }
        $this->periodo = $this->generarDiasSemana($this->semana->fecha_inicio, $this->semana->fecha_fin);
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
                $totalesDiarios[$diaKey] = [
                    'horas' => 0,
                    'monto' => 0,
                    'bono' => 0,
                    'cuadrilleros' => []
                ];
            }

            $this->cuadrilleros = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $this->cuaAsistenciaSemanalId)
                ->get()
                ->filter(function ($grupo) {
                    // Filtramos para que solo pasen los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia()->exists();
                })
                ->map(function ($grupo) use ($periodo, $fechaInicio, $fechaFin, &$totalesDiarios) {
                    // Ahora mapeamos solo los grupos que tienen cuadrilleros
                    return $grupo->cuadrillerosEnAsistencia->map(function ($cuadrilleroDeAsistencia) use ($grupo, $periodo, $fechaInicio, $fechaFin, &$totalesDiarios) {

                        $cuadrilleroData = [
                            'cua_id' => $cuadrilleroDeAsistencia->cua_id,
                            'cua_asi_sem_cua_id' => $cuadrilleroDeAsistencia->id,
                            'cua_asi_sem_gru_id' => $grupo->id,
                            'dni' => $cuadrilleroDeAsistencia->cuadrillero->dni,
                            'color' => $grupo->grupo->color,
                            'codigo_grupo' => $grupo->gru_cua_cod,
                            'nombres' => $cuadrilleroDeAsistencia->cuadrillero->nombres,
                            'monto' => $cuadrilleroDeAsistencia->monto_recaudado
                        ];

                        $horasRegistradas = CuadrillaHora::where('cua_asi_sem_cua_id', $cuadrilleroDeAsistencia->id)
                            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->get()
                            ->keyBy(function ($hora) {
                                return 'dia_' . Carbon::parse($hora->fecha)->day;
                            });

                        foreach ($periodo as $fecha) {
                            $diaKey = 'dia_' . $fecha->day;
                            $horas = $horasRegistradas->get($diaKey)->horas ?? null;
                            $horasContabilizadas = $horasRegistradas->get($diaKey)->horas_contabilizadas ?? null;
                            $bono = $horasRegistradas->get($diaKey)->bono ?? null;
                            $monto = $horasRegistradas->get($diaKey)->costo_dia ?? null;


                            $cuadrilleroData[$diaKey] = $horas;
                            $cuadrilleroData[$diaKey . '_monto'] = $monto;
                            $cuadrilleroData[$diaKey . '_bono'] = $bono;
                            $cuadrilleroData[$diaKey . '_contabilizado'] = $horasContabilizadas != null && $horasContabilizadas == $horas;

                            if ($horas > 0) {
                                // Contar cuadrillero único si tiene horas trabajadas
                                $totalesDiarios[$diaKey]['cuadrilleros'][$cuadrilleroDeAsistencia->id] = true;
                            }

                            // Acumular en los totales diarios
                            $totalesDiarios[$diaKey]['horas'] += $horas;
                            $totalesDiarios[$diaKey]['bono'] += $bono;
                            $totalesDiarios[$diaKey]['monto'] += $monto;
                        }

                        return $cuadrilleroData;
                    });
                })
                ->flatten(1)
                ->sortBy(['codigo_grupo', 'created_at'])
                ->values();

            $totalesData = [
                'cua_id' => '',
                'cua_asi_sem_cua_id' => '',
                'cua_asi_sem_gru_id' => '',
                'dni' => '',
                'color' => '',
                'codigo_grupo' => 'TOTALES',
                'nombres' => '',
                'monto' => array_sum(array_column($totalesDiarios, 'monto')) + array_sum(array_column($totalesDiarios, 'bono'))
            ];


            foreach ($totalesDiarios as $diaKey => $totales) {
                $totalesData[$diaKey] = count($totales['cuadrilleros']); // Total de cuadrilleros únicos por día
                $totalesData[$diaKey . '_monto'] = round($totales['monto'], 2);
                $totalesData[$diaKey . '_bono'] = round($totales['bono'], 2);

            }
            $this->cuadrilleros[] = $totalesData;
        }
    }
    public function actualizarEstadoGrupoEnSemana($cuadrillaId, $valor)
    {
        $grupoSemanal = $this->semana->grupos()->find($cuadrillaId);
        if ($grupoSemanal) {
            $grupoSemanal->estado_pago = $valor;
            $grupoSemanal->save();
            $this->alert('success', 'Estado modificado exitosamente');
        }
    }
    public function actualizarFechaGrupoEnSemana($cuadrillaId, $valor)
    {
        $grupoSemanal = $this->semana->grupos()->find($cuadrillaId);
        if ($grupoSemanal) {
            $grupoSemanal->fecha_pagado = $valor;
            $grupoSemanal->save();
            $this->alert('success', 'Fecha modificada exitosamente');
        }
    }
    public function generarDiasSemana($inicio, $fin)
    {
        if ($inicio && $fin) {
            $inicio = Carbon::parse($inicio);
            $fin = Carbon::parse($fin);
            $periodo = CarbonPeriod::create($inicio, $fin);
            $diasSemana = [];
            $arrEs = [
                'MONDAY' => 'LUN',
                'TUESDAY' => 'MAR',
                'WEDNESDAY' => 'MIE',
                'THURSDAY' => 'JUE',
                'FRIDAY' => 'VIE',
                'SATURDAY' => 'SAB',
                'SUNDAY' => 'DOM'
            ];
            foreach ($periodo as $fecha) {
                $nombre = $arrEs[mb_strtoupper($fecha->isoFormat('dddd'))];

                $diasSemana[] = [
                    'dia' => $fecha->day,
                    'nombre' => $nombre,
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
        $this->semana->actualizarTotales();
        $this->obtenerCuadrilleros();
        $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);

    }

    public function cuadrillerosAgregadosAsistencia()
    {
        $this->semana->actualizarTotales();
        $this->obtenerCuadrilleros();
        $this->dispatch('obtenerCuadrilleros', $this->cuadrilleros);
    }
    public function updatedPrecios($valor, $indices)
    {
        try {
            list($grupoId, $semanaIndice) = explode('.', $indices);

            if (!$this->semana)
                return;


            $fechaInicio = Carbon::parse($this->semana->fecha_inicio);

            $fecha = $fechaInicio->addDays((int) $semanaIndice);
            $costoDia = (float) $valor;
            $costoHora = $costoDia / 8;

            $grupo = CuaAsistenciaSemanalGrupo::find($grupoId);

            if (!$grupo) {
                throw new Exception("No se encontró el grupo");
            }

            $precioBase = $grupo->costo_dia;

            if (trim($valor) == "" || ($precioBase == $costoDia)) {
                CuaAsistenciaSemanalGrupoPrecios::where([
                    'cua_asistencia_semanal_grupo_id' => $grupoId,
                    'fecha' => $fecha->format('Y-m-d'),
                    'cua_asi_sem_cua_id' => null
                ])->delete();

            } else {
                CuaAsistenciaSemanalGrupoPrecios::updateOrCreate([
                    'cua_asistencia_semanal_grupo_id' => $grupoId,
                    'fecha' => $fecha->format('Y-m-d'),
                    'cua_asi_sem_cua_id' => null
                ], [
                    'cua_asi_sem_id' => $this->semana->id,
                    'gru_cua_cod' => $grupo->gru_cua_cod,
                    'costo_dia' => $costoDia,
                    'costo_hora' => $costoHora,
                ]);
            }

            $this->cuadrillerosAgregadosAsistencia();
            $this->obtenerObservaciones();
            $this->alert('success', 'Costo modificado satisfactoriamente.');


        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        if ($this->gruposTotales && $this->periodo) {
            if ($this->semana) {
                $fechaInicio = Carbon::parse($this->semana->fecha_inicio);

                // Cargar todos los precios personalizados en un array indexado
                $preciosPersonalizados = CuaAsistenciaSemanalGrupoPrecios::whereNull('cua_asi_sem_cua_id')
                    ->whereBetween('fecha', [$this->semana->fecha_inicio, $this->semana->fecha_fin])
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->cua_asistencia_semanal_grupo_id . '_' . $item->fecha;
                    });

                foreach ($this->gruposTotales as $grupoSemanal) {
                    foreach ($this->periodo as $semanaIndice => $periodo) {
                        $fecha = $fechaInicio->copy()->addDays((int) $semanaIndice);
                        $fechaStr = $fecha->toDateString();

                        // Establecer el costo por día predeterminado
                        $costoDia = $grupoSemanal->costo_dia;

                        // Verificar si hay un precio personalizado para el grupo y fecha
                        $personalizadoKey = $grupoSemanal->id . '_' . $fechaStr;

                        $this->precios[$grupoSemanal->id][$semanaIndice]['base'] = true;
                        if (isset($preciosPersonalizados[$personalizadoKey])) {
                            $personalizado = $preciosPersonalizados[$personalizadoKey]->first();
                            $costoDia = $personalizado->costo_dia;
                            $this->precios[$grupoSemanal->id][$semanaIndice]['base'] = false;
                        }

                        // Almacenar el costo en la propiedad de precios
                        $this->precios[$grupoSemanal->id][$semanaIndice]['costo_dia'] = $costoDia;
                    }
                }
            }
        }


        return view('livewire.cuadrilla-asistencia-detalle-component');
    }
}
