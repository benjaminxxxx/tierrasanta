<?php

namespace App\Services\Cuadrilla\RegistroDiario;

use App\Models\CuadTramoLaboral;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lectura del registro diario de cuadrilla: el detalle de horas (tramos campo/labor) y bonos de varios cuadrilleros
 * en varios días de un tramo laboral. Lo usa el modal "Ver detalle de las horas" del reporte semanal.
 */
class CuadrillaRegistroDiarioConsulta
{
    /**
     * @param int[] $cuadrilleroIds
     * @param string[] $fechas Y-m-d (se descartan las que están fuera del tramo)
     * @return array{
     *   dias: array<int, array{fecha:string, horas:float, filas: array<int, array>}>,
     *   leyenda: array<int, array{codigo:int, labor:string}>
     * }
     */
    public function detalleHoras(CuadTramoLaboral $tramo, array $cuadrilleroIds, array $fechas): array
    {
        $inicio = Carbon::parse($tramo->fecha_inicio)->toDateString();
        $fin = Carbon::parse($tramo->fecha_fin)->toDateString();
        $fechas = collect($fechas)->map(fn($f) => Carbon::parse($f)->toDateString())
            ->filter(fn($f) => $f >= $inicio && $f <= $fin)->unique()->sort()->values()->all();
        $cuadrilleroIds = array_values(array_unique(array_map('intval', $cuadrilleroIds)));
        if (!$fechas || !$cuadrilleroIds) {
            return ['dias' => [], 'leyenda' => []];
        }

        // Cuadrilleros del tramo en el orden del reporte (grupo, orden), con su grupo y color
        $cuadrilleros = DB::table('cuad_tramo_cuadrilleros as tc')
            ->join('cuad_tramo_grupos as tg', 'tg.id', '=', 'tc.cuad_tramo_laboral_grupo_id')
            ->leftJoin('cuad_grupos as g', 'g.codigo', '=', 'tg.codigo_grupo')
            ->leftJoin('cuad_cuadrilleros as c', 'c.id', '=', 'tc.cuadrillero_id')
            ->where('tg.cuad_tramo_laboral_id', $tramo->id)
            ->whereIn('tc.cuadrillero_id', $cuadrilleroIds)
            ->orderBy('tg.orden')->orderBy('tc.orden')
            ->get(['tc.cuadrillero_id', DB::raw('COALESCE(c.nombres, tc.nombres) AS nombres'), 'tg.codigo_grupo', 'g.color']);

        $registros = DB::table('cuad_registros_diarios')
            ->where('tramo_laboral_id', $tramo->id)
            ->whereIn('cuadrillero_id', $cuadrilleroIds)
            ->whereIn('fecha', $fechas)
            ->get(['id', 'cuadrillero_id', 'codigo_grupo', 'fecha', 'total_horas', 'jornal_aplicado', 'costo_personalizado_dia',
                'total_bono', 'esta_pagado', 'desglose_detalle_id'])
            ->keyBy(fn($r) => $r->cuadrillero_id . '|' . $r->codigo_grupo . '|' . Carbon::parse($r->fecha)->toDateString());

        $ids = $registros->pluck('id');
        $tramos = DB::table('cuad_detalles_horas')->whereIn('registro_diario_id', $ids)
            ->orderBy('hora_inicio')
            ->get(['registro_diario_id', 'campo_nombre', 'codigo_labor', 'hora_inicio', 'hora_fin'])
            ->groupBy('registro_diario_id');
        $bonos = DB::table('cuad_bonos_actividades as b')
            ->join('actividades as a', 'a.id', '=', 'b.actividad_id')
            ->whereIn('b.registro_diario_id', $ids)
            ->where('b.total_bono', '!=', 0)
            ->get(['b.registro_diario_id', 'b.total_bono', 'a.campo', 'a.codigo_labor'])
            ->groupBy('registro_diario_id');

        $codigos = [];
        $dias = [];
        foreach ($fechas as $fecha) {
            $filas = [];
            foreach ($cuadrilleros as $c) {
                $r = $registros[$c->cuadrillero_id . '|' . $c->codigo_grupo . '|' . $fecha] ?? null;
                $detalle = $r ? ($tramos[$r->id] ?? collect())->map(function ($t) use (&$codigos) {
                    if ($t->codigo_labor !== null) {
                        $codigos[(int) $t->codigo_labor] = true;
                    }
                    $horas = round((strtotime($t->hora_fin) - strtotime($t->hora_inicio)) / 3600, 2);
                    return ['inicio' => substr($t->hora_inicio, 0, 5), 'fin' => substr($t->hora_fin, 0, 5), 'horas' => $horas,
                        'campo' => $t->campo_nombre, 'codigo' => $t->codigo_labor !== null ? (int) $t->codigo_labor : null];
                })->values()->all() : [];
                $bonosDia = $r ? ($bonos[$r->id] ?? collect())->map(function ($b) use (&$codigos) {
                    $codigos[(int) $b->codigo_labor] = true;
                    return ['campo' => $b->campo, 'codigo' => (int) $b->codigo_labor, 'bono' => (float) $b->total_bono];
                })->values()->all() : [];

                $filas[] = [
                    'cuadrillero_id' => $c->cuadrillero_id,
                    'nombres' => $c->nombres,
                    'grupo' => $c->codigo_grupo,
                    'color' => $c->color,
                    'registrado' => (bool) $r,
                    'horas' => $r ? (float) $r->total_horas : 0.0,
                    'horas_tramos' => round(array_sum(array_column($detalle, 'horas')), 2),
                    'jornal' => $r ? (float) ($r->costo_personalizado_dia ?? $r->jornal_aplicado) : 0.0,
                    'personalizado' => $r && $r->costo_personalizado_dia !== null,
                    'bono' => $r ? (float) $r->total_bono : 0.0,
                    'pagado' => $r && ($r->esta_pagado || $r->desglose_detalle_id),
                    'tramos' => $detalle,
                    'bonos' => $bonosDia,
                ];
            }
            $dias[] = ['fecha' => $fecha, 'horas' => round(array_sum(array_column($filas, 'horas')), 2), 'filas' => $filas];
        }

        ksort($codigos);
        // Si un código tuvo varias labores (vigencias), gana la activa: va al final y pisa a las desactivadas
        $nombres = DB::table('labores')->whereIn('codigo', array_keys($codigos))
            ->orderByRaw('deleted_at IS NULL')->orderBy('id')->pluck('nombre_labor', 'codigo');
        $leyenda = array_map(fn($codigo) => ['codigo' => $codigo, 'labor' => $nombres[$codigo] ?? 'Labor sin nombre'], array_keys($codigos));

        return ['dias' => $dias, 'leyenda' => $leyenda];
    }
}
