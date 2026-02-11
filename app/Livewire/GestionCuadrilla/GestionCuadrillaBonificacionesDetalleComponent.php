<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Actividad;
use App\Models\CuadRegistroDiario;
use App\Models\Labores;
use App\Models\PlanRegistroDiario;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaBonificacionesDetalleComponent extends Component
{
    use LivewireAlert;
    public $actividad;
    public $tramos = [
        ['hasta' => '', 'monto' => '']
    ];
    public $estandarProduccion = 0;
    public $unidades = 'kg.';
    public $recojos = 1;
    public $tableDataBonificados = [];
    public function mount($actividadSeleccionada)
    {
        $this->actividad = Actividad::findOrFail($actividadSeleccionada);

        $this->obtenerRecojos();
        $this->obtenerParametrosBono();
        $this->obtenerTrabajadores();

    }
    public function obtenerRecojos()
    {
        // Set recojos (o numero_tramos, si así lo llamas ahora)
        $this->recojos = $this->actividad->recojos ?? 1;
    }
    public function obtenerParametrosBono()
    {
        // Si la actividad ya tiene configurados sus tramos/producción
        if (!empty($this->actividad->tramos_bonificacion)) {
            $this->tramos = json_decode($this->actividad->tramos_bonificacion, true) ?? [['hasta' => '', 'monto' => '']];
            $this->estandarProduccion = $this->actividad->estandar_produccion;
            $this->unidades = $this->actividad->unidades ?? 'kg';
        }
        // Si no, buscar la labor asociada por código
        else {
            $labor = Labores::where('codigo', $this->actividad->codigo_labor)->first();

            if ($labor) {
                $this->tramos = json_decode($labor->tramos_bonificacion, true) ?? [['hasta' => '', 'monto' => '']];
                $this->estandarProduccion = $labor->estandar_produccion;
                $this->unidades = $labor->unidades ?? 'kg';
            }
        }
    }
    public function obtenerTrabajadores()
    {
        try {
            $campoNombre = $this->actividad->campo;
            $codigoLabor = $this->actividad->codigo_labor;
            $fecha = $this->actividad->fecha;

            $registros = $this->obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);

            $registrosPlanilla = $this->obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);
            //dd($registrosPlanilla);
            //dd($registros,$registrosPlanilla);
            //hasta aqui me quede la revision, falta planilla
            $dataHandsontable = [];
            $dataHandsontablePlanilla = [];
            foreach ($registros as $registro) {

                $totalBonoCalculado = $registro->actividadesBonos->sum(function ($actividadBono) {
                    return $actividadBono->total_bono ?? 0;
                });

                $row = [
                    'registro_diario_id' => $registro->id,
                    'tipo' => 'CUADRILLA',
                    'cuadrillero_id' => $registro->cuadrillero_id,
                    'nombre_trabajador' => optional($registro->cuadrillero)->nombres ?? '-',
                    'campo' => $campoNombre,
                    'labor' => $codigoLabor,
                    'total_bono' => $totalBonoCalculado,
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

                $row = [
                    'registro_diario_id' => $registroPlanilla->id,
                    'tipo' => 'PLANILLA',
                    'nombre_trabajador' => $registroPlanilla->detalleMensual->nombres,
                    'campo' => $campoNombre,
                    'labor' => $codigoLabor,
                    'total_bono' => $totalBonoCalculado,
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

            $estandarProduccion = $this->estandarProduccion == '' ? null : (float)$this->estandarProduccion;
            $this->actividad->update([
                'tramos_bonificacion' => json_encode($this->tramos),
                'unidades' => $this->unidades,
                'estandar_produccion' => $estandarProduccion,
                'recojos' => $this->recojos
            ]);

            EmpleadoServicio::guardarBonificaciones($this->actividad, $datos, $this->recojos);


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