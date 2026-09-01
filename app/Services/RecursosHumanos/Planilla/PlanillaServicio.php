<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\Actividad;
use App\Models\PlanActividadBono;
use App\Models\PlanActividadProduccion;
use App\Models\PlanRegistroDiario;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioDetalle;
use App\Services\PlanillaMensualServicio;
use App\Support\DateHelper;
use Exception;

class PlanillaServicio
{
    public function listarPlanillaMensual($mes,$anio){
        $lista = app(PlanillaMensualServicio::class)->obtenerPlanillaXMesAnio($mes,$anio,'nombres');
        
        return $lista->map(function ($reporte){
            
            return [
                'documento'=>$reporte->documento,
                'nombres'=>$reporte->nombres,
                'sueldo_negro',
                'sueldo_blanco'
            ];
        })
        ->toArray();
    }
    public static function guardarBonoPlanilla($fila, $numeroRecojos, $actividadId,$mapaMetodos)
    {

        $registroDiarioId = $fila['registro_diario_id'] ?? null;
        $metodoBonificacion = $fila['metodo_bonificacion'] ?? null;

        if (!$registroDiarioId) {
            throw new Exception("Falta el parámetro de identificación de reporte diario");
        }

        $metodoId = $mapaMetodos[$metodoBonificacion] ?? null;

        $actividadBono = PlanActividadBono::updateOrCreate(
            [
                'registro_diario_id' => $registroDiarioId,
                'actividad_id' => $actividadId
            ],
            [
                'metodo_id' => $metodoId,
                'total_bono' => $fila['total_bono'] ?? 0,
                'bono_manual' => (bool) ($fila['bono_manual'] ?? false),
            ]
        );

        PlanActividadProduccion::where('actividad_bono_id', $actividadBono->id)
            ->where('numero_recojo', '>', $numeroRecojos)
            ->delete();

        for ($i = 1; $i <= $numeroRecojos; $i++) {
            $produccion = $fila['produccion_' . $i] ?? null;

            if ($produccion) {
                PlanActividadProduccion::updateOrCreate(
                    [
                        'actividad_bono_id' => $actividadBono->id,
                        'numero_recojo' => $i
                    ],
                    [
                        'produccion' => $produccion
                    ]
                );
            } else {
                PlanActividadProduccion::where('actividad_bono_id', $actividadBono->id)
                    ->where('numero_recojo', $i)
                    ->delete();
            }
        }

        $sumaBonos = PlanActividadBono::where('registro_diario_id', $registroDiarioId)->sum('total_bono');

        $registroDiario = PlanRegistroDiario::findOrFail($registroDiarioId);
        $registroDiario->update([
            'total_bono' => $sumaBonos
        ]);
    }
    public static function obtenerTrabajadoresPlanillaPorCampoYLabor($fecha, $campo, $labor)
    {
        return ReporteDiario::where('fecha', $fecha)
            ->whereHas('detalles', function ($query) use ($campo, $labor) {
                $query->where('campo', $campo);
                $query->where('labor', $labor);
            })->with([
                    'detalles' => function ($query) use ($campo, $labor) {
                        $query->where('campo', $campo)
                            ->where('labor', $labor);
                    }
                ]);
    }
    public static function obtenerHandsontableRegistrosPorActividad($actividadId)
    {

        $actividad = Actividad::find($actividadId);
        if (!$actividad) {
            throw new Exception('No existe la actividad');
        }
        $fecha = $actividad->fecha;
        $campo = $actividad->campo;
        $labor = $actividad->codigo_labor;
        $registros = self::obtenerTrabajadoresPlanillaPorCampoYLabor($fecha, $campo, $labor)->get();

        $horariosUnicos = collect();
        foreach ($registros as $r) {
            foreach ($r->detalles as $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_salida)->format('H:i');
                $horariosUnicos->push("$inicio-$fin");
            }
        }

        $horariosUnicos = $horariosUnicos->unique()->values()->slice(0, 10);

        // 🟩 Preparar filas para Handsontable
        $data = [];
        $maxTramos = 0;

        foreach ($registros as $r) {

            $row = [
                'planilla_dni' => $r->documento,
                'nombre_trabajador' => $r->empleado_nombre,
                'campo' => $campo,
                'labor' => $labor,
                'total_bono' => 0,
            ];

            $detalles = $r->detalles;
            $bono = 0;
            $horariosConcatenados = [];

            foreach ($detalles as $i => $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_salida)->format('H:i');
                $key = $inicio . '-' . $fin;
                $row["produccion_" . ($i + 1)] = $d->produccion ?? 0;
                $bono += $d->costo_bono ?? 0;
                $horariosConcatenados[] = $key;
            }

            $maxTramos = max($maxTramos, $detalles->count());
            $row['horarios'] = implode(',', $horariosConcatenados);
            $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
            $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);
            $row['total_bono'] = $bono;

            $data[] = $row;
        }

        return [
            'data' => $data,
            'total_horarios' => $maxTramos,
        ];
    }

}
