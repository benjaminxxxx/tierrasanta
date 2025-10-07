<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\Actividad;
use App\Models\PlanActividadBono;
use App\Models\PlanActividadProduccion;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioDetalle;
use App\Support\DateHelper;
use Exception;

class PlanillaServicio
{
    public static function calcularBonosTotalesPlanilla($fecha)
    {
        $reportes = ReporteDiario::whereDate('fecha', $fecha)
            ->with(['detalles'])
            ->get();

        foreach ($reportes as $reporte) {
            $bono_productividad = $reporte->detalles->sum('costo_bono');
            $reporte->update([
                'bono_productividad' => $bono_productividad,
            ]);
        }
    }
    public static function guardarBonoPlanilla($fila, $numeroRecojos, $actividadId)
    {

        $registroDiarioId = $fila['registro_diario_id'] ?? null;

        if (!$registroDiarioId) {
            throw new Exception("Falta el parámetro de identificación de reporte diario");
        }

        $actividadBono = PlanActividadBono::updateOrCreate(
            [
                'registro_diario_id' => $registroDiarioId,
                'actividad_id' => $actividadId
            ],
            [
                'total_bono' => $fila['total_bono'] ?? 0
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

        $registroDiario = ReporteDiario::findOrFail($registroDiarioId);
        $registroDiario->update([
            'bono_productividad' => $sumaBonos
        ]);

        //dd($registroDiario);
/*
        $fecha = now();

        $planillaDni = $fila['planilla_dni'] ?? null;
        $campo = $fila['campo'] ?? null;
        $labor = $fila['labor'] ?? null;
        $totalBono = floatval($fila['total_bono'] ?? 0);

        // Buscar el registro diario para la fecha
        $registro = ReporteDiario::where('documento', $planillaDni)
            ->whereDate('fecha', $fecha)
            ->with(['detalles'])
            ->first();

        if (!$registro) {
            return;
        }

        // Obtener detalles de esa actividad, ordenados por horario
        $detalles = $registro->detalles()
            ->where('campo', $campo)
            ->where('labor', $labor)
            ->orderBy('hora_salida')
            ->get();

        $conteoTramos = $detalles->count();

        if ($conteoTramos === 0) {
            return;
        }

        // Calcular bono proporcional por tramo
        $bonoPorTramo = round($totalBono / $conteoTramos, 2);

        // Recolectar solo los valores de producción válidos
        $producciones = [];
        for ($i = 1; $i <= $conteoTramos; $i++) {
            $produccionKey = "produccion_$i";
            $producciones[] = isset($fila[$produccionKey]) ? floatval($fila[$produccionKey]) : 0;
        }

        // Actualizar cada detalle con costo_bono y producción
        foreach ($detalles as $index => $detalle) {
            $detalle->update([
                'costo_bono' => $bonoPorTramo,
                'produccion' => $producciones[$index] ?? 0
            ]);
        }*/
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
