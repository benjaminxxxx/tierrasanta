<?php

namespace App\Services;

use App\Models\RepActividadDiaria;
use Illuminate\Support\Facades\DB;

class ActividadesResumenServicio
{
    /**
     * Recalcula un día consultando la vista y persiste en rep_actividades_diarias.
     * Único lugar donde se toca la vista lenta.
     */
    public function recalcularDia(string $fecha): void
    {
        // Consulta la vista (lenta, solo se llama al actualizar)
        $filas = DB::table('v_reporte_actividades_diario')
            ->whereDate('fecha', $fecha)
            ->get();

        // Borra el día y reinserta (más limpio que upsert con N campos)
        RepActividadDiaria::whereDate('fecha', $fecha)->delete();

        if ($filas->isEmpty()) return;

        $ahora = now();
        $insert = $filas->map(fn($f) => [
            'fecha'           => $f->fecha,
            'campo'           => $f->campo,
            'codigo_labor'    => $f->codigo_labor,
            'nombre_labor'    => $f->nombre_labor,
            'unidades'        => $f->unidades    ?? 0,
            'recojos'         => $f->recojos     ?? 0,
            'total_metodos'   => $f->total_metodos   ?? 0,
            'total_planilla'  => $f->total_planilla  ?? 0,
            'total_cuadrilla' => $f->total_cuadrilla ?? 0,
            'actividad_id'    => $f->actividad_id,
            'created_at'      => $ahora,
            'updated_at'      => $ahora,
        ])->toArray();

        // Insert en lote — una sola query
        RepActividadDiaria::insert($insert);
    }

    /**
     * Lee desde la tabla estática. Rápido siempre.
     * $agruparPor: 'actividad' (default) | 'campo'
     */
    public function cargarDia(string $fecha, string $agruparPor = 'actividad'): \Illuminate\Support\Collection
    {
        $query = RepActividadDiaria::whereDate('fecha', $fecha);

        if ($agruparPor === 'campo') {
            return $query->orderBy('campo')
                         ->orderByDesc(DB::raw('total_planilla + total_cuadrilla'))
                         ->get();
        }

        // Por actividad: ordenar por mayor total de personas
        return $query->orderByDesc(DB::raw('total_planilla + total_cuadrilla'))
                     ->orderBy('nombre_labor')
                     ->get();
    }

    /**
     * Para el reporte mensual: agrega todos los días del mes.
     * Pura SQL sobre la tabla estática — muy rápido.
     */
    public function cargarMes(int $mes, int $anio): \Illuminate\Support\Collection
    {
        return RepActividadDiaria::whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->select(
                'campo',
                'codigo_labor',
                'nombre_labor',
                DB::raw('SUM(total_planilla)  AS total_planilla'),
                DB::raw('SUM(total_cuadrilla) AS total_cuadrilla'),
                DB::raw('SUM(total_metodos)   AS total_metodos'),
                DB::raw('COUNT(*)             AS dias_activos'),
            )
            ->groupBy('campo', 'codigo_labor', 'nombre_labor')
            ->orderByDesc(DB::raw('SUM(total_planilla + total_cuadrilla)'))
            ->get();
    }

    /**
     * Para el reporte anual: agrega por mes.
     */
    public function cargarAnio(int $anio): \Illuminate\Support\Collection
    {
        return RepActividadDiaria::whereYear('fecha', $anio)
            ->select(
                DB::raw('MONTH(fecha)         AS mes'),
                DB::raw('SUM(total_planilla)  AS total_planilla'),
                DB::raw('SUM(total_cuadrilla) AS total_cuadrilla'),
                DB::raw('SUM(total_metodos)   AS total_metodos'),
                DB::raw('COUNT(DISTINCT CONCAT(campo, codigo_labor)) AS actividades_distintas'),
            )
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->orderBy(DB::raw('MONTH(fecha)'))
            ->get();
    }
}