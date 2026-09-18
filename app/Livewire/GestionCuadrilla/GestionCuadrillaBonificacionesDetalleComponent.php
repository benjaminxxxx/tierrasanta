<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Actividad;
use App\Models\CuadRegistroDiario;
use App\Models\PlanRegistroDiario;
use App\Services\Bonificacion\GuardarBonificacionProceso;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaBonificacionesDetalleComponent extends Component
{
    use LivewireAlert;
    public $actividad;
    public $metodos = [];
    public $estandarProduccion = 0;
    public $unidades = 'kg.';
    public $recojos = 1;
    public $tableDataBonificados = [];
    public function mount($actividadSeleccionada)
    {
        $this->actividad = Actividad::find($actividadSeleccionada);


        if ($this->actividad) {
            $this->recojos = $this->actividad->recojos;
            $this->metodos = $this->actividad->metodos()->with(['tramos'])->get()->toArray();
            $this->unidades = $this->actividad->unidades ?? 'kg';
        }

        //los trabajadores deben estar despues de los recojos para obtener la informacion completa de recojos
        $this->obtenerTrabajadores();

    }
    /*
    public function obtenerTrabajadores()
    {
        try {
            $campoNombre = $this->actividad->campo;
            $codigoLabor = $this->actividad->codigo_labor;
            $fecha = $this->actividad->fecha;

            $registros = $this->obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);
            $registrosPlanilla = $this->obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);

            $dataHandsontable = [];
            $dataHandsontablePlanilla = [];

            foreach ($registros as $registro) {

                $totalBonoCalculado = $registro->actividadesBonos->sum(function ($actividadBono) {
                    return $actividadBono->total_bono ?? 0;
                });
                $metodo_bonificacion = optional(optional($registro->actividadesBonos->first())->metodo)->titulo;
                $row = [
                    'registro_diario_id' => $registro->id,
                    'tipo' => 'CUADRILLA',
                    'metodo_bonificacion' => $metodo_bonificacion,
                    'cuadrillero_id' => $registro->cuadrillero_id,
                    'nombre_trabajador' => optional($registro->cuadrillero)->nombres ?? '-',
                    'campo' => $campoNombre,
                    'labor' => $codigoLabor,
                    'total_bono' => $totalBonoCalculado,
                    'bono_manual'        => (bool) optional($registro->actividadesBonos->first())->bono_manual,
                ];

                // Ahora las producciones están dentro de actividadesBonos
                $producciones = collect();
                foreach ($registro->actividadesBonos as $actividadBono) {
                    foreach ($actividadBono->producciones as $produccion) {
                        $producciones->push($produccion);
                    }
                }
                $recojos = $producciones->keyBy('numero_recojo');

                for ($i = 0; $i < $this->recojos; $i++) {
                    $numeroRecojo = $i + 1;
                    $row['produccion_' . $numeroRecojo] = isset($recojos[$numeroRecojo])
                        ? $recojos[$numeroRecojo]->produccion
                        : '';
                }

                $horariosConcatenados = [];
                foreach ($registro->detalleHoras as $detalle) {
                    $inicio = Carbon::parse($detalle->hora_inicio)->format('H:i');
                    $fin = Carbon::parse($detalle->hora_fin)->format('H:i');
                    $key = "$inicio-$fin";
                    $horariosConcatenados[] = $key;
                }

                $row['horarios'] = implode(',', $horariosConcatenados);
                $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
                $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);

                $dataHandsontable[] = $row;
            }


            foreach ($registrosPlanilla as $registroPlanilla) {

                $totalBonoCalculado = $registroPlanilla->actividadesBonos->sum(function ($actividadBono) {
                    return $actividadBono->total_bono ?? 0;
                });


                $metodo_bonificacion = optional(optional($registroPlanilla->actividadesBonos->first())->metodo)->titulo;

                $row = [
                    'registro_diario_id' => $registroPlanilla->id,
                    'tipo' => 'PLANILLA',
                    'metodo_bonificacion' => $metodo_bonificacion,
                    'nombre_trabajador' => $registroPlanilla->detalleMensual->nombres,
                    'campo' => $campoNombre,
                    'labor' => $codigoLabor,
                    'total_bono' => $totalBonoCalculado,
                    'bono_manual'        => (bool) optional($registroPlanilla->actividadesBonos->first())->bono_manual,
                ];

                $horariosConcatenados = [];

                $producciones = collect();
                foreach ($registroPlanilla->actividadesBonos as $actividadBono) {
                    foreach ($actividadBono->producciones as $produccion) {
                        $producciones->push($produccion);
                    }
                }
                $recojos = $producciones->keyBy('numero_recojo');

                for ($i = 0; $i < $this->recojos; $i++) {
                    $numeroRecojo = $i + 1;
                    $row['produccion_' . $numeroRecojo] = isset($recojos[$numeroRecojo])
                        ? $recojos[$numeroRecojo]->produccion
                        : '';
                }
                foreach ($registroPlanilla->detalles as $detalle) {
                    $inicio = Carbon::parse($detalle->hora_inicio)->format('H:i');
                    $fin = Carbon::parse($detalle->hora_fin)->format('H:i');
                    $key = "$inicio-$fin";
                    $horariosConcatenados[] = $key;
                }

                $row['horarios'] = implode(',', $horariosConcatenados);

                $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
                $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);

                $dataHandsontable[] = $row;
            }
            $this->tableDataBonificados = $dataHandsontable;
        } catch (\Throwable $th) {

            $this->alert('error', $th->getMessage());
        }
    }*/
    public function obtenerTrabajadores()
    {
        try {
            $campoNombre = $this->actividad->campo;
            $codigoLabor = $this->actividad->codigo_labor;
            $fecha = $this->actividad->fecha;

            // Carga de registros usando tus métodos de consulta
            $registros = $this->obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);
            $registrosPlanilla = $this->obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);

            $dataHandsontable = [];

            // 1. Procesar Cuadrillas
            foreach ($registros as $registro) {
                $dataHandsontable[] = $this->mapearFilaTrabajador(
                    $registro,
                    'CUADRILLA',
                    optional($registro->cuadrillero)->nombres ?? '-',
                    $registro->detalleHoras,
                    $campoNombre,
                    $codigoLabor
                );
            }

            // 2. Procesar Planillas
            foreach ($registrosPlanilla as $registroPlanilla) {
                $dataHandsontable[] = $this->mapearFilaTrabajador(
                    $registroPlanilla,
                    'PLANILLA',
                    $registroPlanilla->detalleMensual->nombres ?? '-',
                    $registroPlanilla->detalles,
                    $campoNombre,
                    $codigoLabor
                );
            }

            // 3. Acumulación para Totales
            $countCuadrilla = count($registros);
            $countPlanilla = count($registrosPlanilla);

            $totalesCuadrilla = $this->inicializarAcumuladoresTotales();
            $totalesPlanilla = $this->inicializarAcumuladoresTotales();

            foreach ($dataHandsontable as $row) {
                if ($row['tipo'] === 'CUADRILLA') {
                    $refTotales = &$totalesCuadrilla;
                } else {
                    $refTotales = &$totalesPlanilla;
                }
                
                $refTotales['total_bono'] += (float) $row['total_bono'];
                $refTotales['total_horas'] += (float) $row['total_horas'];

                for ($i = 1; $i <= $this->recojos; $i++) {
                    $refTotales['produccion_' . $i] += (float) ($row['produccion_' . $i] ?? 0);
                }

                // Romper la referencia al finalizar la iteración para evitar efectos secundarios
                unset($refTotales);
            }

            // 4. Construcción de Filas de Totales
            $rowTotalCuadrilla = $this->crearFilaTotal(
                "{$countCuadrilla} cuadrilleros",
                $campoNombre,
                $codigoLabor,
                $totalesCuadrilla
            );

            $rowTotalPlanilla = $this->crearFilaTotal(
                "{$countPlanilla} planilleros",
                $campoNombre,
                $codigoLabor,
                $totalesPlanilla
            );

            // Gran Total (Suma de ambos)
            $totalesGranTotal = [
                'total_bono' => $totalesCuadrilla['total_bono'] + $totalesPlanilla['total_bono'],
                'total_horas' => $totalesCuadrilla['total_horas'] + $totalesPlanilla['total_horas'],
            ];
            for ($i = 1; $i <= $this->recojos; $i++) {
                $totalesGranTotal['produccion_' . $i] = $totalesCuadrilla['produccion_' . $i] + $totalesPlanilla['produccion_' . $i];
            }

            $rowGranTotal = $this->crearFilaTotal(
                ($countCuadrilla + $countPlanilla) . " trabajadores (TOTAL)",
                $campoNombre,
                $codigoLabor,
                $totalesGranTotal
            );

            // Anexar filas de totales a la tabla final
            $dataHandsontable[] = $rowTotalCuadrilla;
            $dataHandsontable[] = $rowTotalPlanilla;
            $dataHandsontable[] = $rowGranTotal;

            $this->tableDataBonificados = $dataHandsontable;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    /**
     * Método auxiliar para transformar cada registro individual
     */
    private function mapearFilaTrabajador($registro, string $tipo, string $nombreTrabajador, $detallesHoras, string $campoNombre, string $codigoLabor): array
    {
        $totalBonoCalculado = $registro->actividadesBonos->sum('total_bono');
        $metodo_bonificacion = optional(optional($registro->actividadesBonos->first())->metodo)->titulo;

        $row = [
            'registro_diario_id' => $registro->id,
            'tipo' => $tipo,
            'metodo_bonificacion' => $metodo_bonificacion,
            'cuadrillero_id' => $registro->cuadrillero_id ?? null,
            'nombre_trabajador' => $nombreTrabajador,
            'campo' => $campoNombre,
            'labor' => $codigoLabor,
            'total_bono' => $totalBonoCalculado,
            'bono_manual' => (bool) optional($registro->actividadesBonos->first())->bono_manual,
        ];

        // Mapeo de producciones
        $producciones = $registro->actividadesBonos->pluck('producciones')->flatten()->keyBy('numero_recojo');

        for ($i = 1; $i <= $this->recojos; $i++) {
            $row['produccion_' . $i] = isset($producciones[$i]) ? $producciones[$i]->produccion : '';
        }

        // Mapeo de tramos de horas usando Carbon
        $horariosConcatenados = [];
        foreach ($detallesHoras as $detalle) {
            $inicio = \Carbon\Carbon::parse($detalle->hora_inicio)->format('H:i');
            $fin = \Carbon\Carbon::parse($detalle->hora_fin)->format('H:i');
            $horariosConcatenados[] = "$inicio-$fin";
        }

        $row['horarios'] = implode(',', $horariosConcatenados);

        // Uso directo de tus métodos estáticos auxiliares
        $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
        $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);

        return $row;
    }

    /**
     * Inicializa la estructura para acumular valores numéricos
     */
    private function inicializarAcumuladoresTotales(): array
    {
        $totales = [
            'total_bono' => 0,
            'total_horas' => 0,
        ];

        for ($i = 1; $i <= $this->recojos; $i++) {
            $totales['produccion_' . $i] = 0;
        }

        return $totales;
    }

    /**
     * Construye el array con el formato adecuado para una fila de TOTAL
     */
    private function crearFilaTotal(string $etiquetaTrabajador, string $campoNombre, string $codigoLabor, array $totales): array
    {
        // Convertir horas decimales acumuladas (ej: 12.5) a formato HH:MM (ej: "12:30")
        $horasEnteras = floor($totales['total_horas']);
        $minutos = round(($totales['total_horas'] - $horasEnteras) * 60);
        $rangoFormateado = sprintf('%02d:%02d', $horasEnteras, $minutos);

        $rowTotal = [
            'registro_diario_id' => null,
            'tipo' => 'TOTAL',
            'metodo_bonificacion' => '',
            'nombre_trabajador' => $etiquetaTrabajador,
            'campo' => '',
            'labor' => '',
            'total_bono' => round($totales['total_bono'], 2),
            'bono_manual' => false,
            'horarios' => '',
            'rango_total_horas' => $rangoFormateado,
            'total_horas' => round($totales['total_horas'], 2),
        ];

        for ($i = 1; $i <= $this->recojos; $i++) {
            $rowTotal['produccion_' . $i] = round($totales['produccion_' . $i], 2);
        }

        return $rowTotal;
    }
    public function obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $actividadId)
    {

        $filtroDetalleHoras = function ($query) use ($campoNombre, $codigoLabor) {
            $query->where('campo_nombre', $campoNombre)
                ->where('codigo_labor', $codigoLabor);
        };

        $filtroActividadBono = function ($query) use ($actividadId) {
            $query->where('actividad_id', $actividadId);
        };

        return CuadRegistroDiario::with([
            'cuadrillero:id,nombres',
            'actividadesBonos' => function ($query) use ($filtroActividadBono) {
                $filtroActividadBono($query);
                $query->with('producciones'); // carga las recogidas
            },
            'detalleHoras' => function ($query) use ($filtroDetalleHoras) {
                $filtroDetalleHoras($query);
                $query->orderBy('hora_inicio');
            }
        ])
            ->where('fecha', $fecha)
            ->whereHas('detalleHoras', $filtroDetalleHoras)
            ->get();
    }


    public function obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $actividadId)
    {
        $filtroDetalles = function ($query) use ($campoNombre, $codigoLabor) {
            $query->where('campo_nombre', $campoNombre)
                ->where('codigo_labor', $codigoLabor);
        };

        $filtroActividadBono = function ($query) use ($actividadId) {
            $query->where('actividad_id', $actividadId);
        };

        return PlanRegistroDiario::with([
            'detalleMensual',
            'actividadesBonos' => function ($query) use ($filtroActividadBono) {
                $filtroActividadBono($query);
                $query->with('producciones'); // carga las recogidas
            },
            'detalles' => function ($query) use ($filtroDetalles) {
                $filtroDetalles($query);
                $query->orderBy('hora_inicio');
            }
        ])
            ->where('fecha', $fecha)
            ->whereHas('detalles', $filtroDetalles)
            ->get();
    }

    public function guardarBonificaciones($datos)
    {
        try {

            if (!$this->actividad) {
                throw new Exception("La actividad ha caducado");
            }

            GuardarBonificacionProceso::ejecutar(
                $this->actividad,
                $this->metodos,
                $this->unidades,
                $this->recojos,
                $datos
            );

            $this->alert('success', 'Datos guardados correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component');
    }
}