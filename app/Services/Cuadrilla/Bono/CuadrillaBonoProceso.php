<?php

namespace App\Services\Cuadrilla\Bono;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Escrituras ligadas a los bonos de cuadrilla que no hace la pantalla de bonificaciones:
 * - horas_destajo cuando solo cambia el flag "respeta el jornal" (los triggers no se disparan);
 * - el bono "respeta el jornal" depende del jornal: si cambia el jornal (costo del grupo o personalizado), el bono
 *   cambia. Primero se simula y se muestra a quién afecta; al confirmar, se guarda y se recalculan.
 *   Los bonos ya pagados o manuales no se tocan.
 */
class CuadrillaBonoProceso
{
    /** Recalcula horas_destajo de los registros diarios de cuadrilla con bono en esta actividad (mismo criterio que los triggers). */
    public static function recalcularHorasDestajo(int $actividadId): void
    {
        $registros = DB::table('cuad_bonos_actividades')->where('actividad_id', $actividadId)->pluck('registro_diario_id')->unique();
        foreach ($registros as $id) {
            $horas = (float) DB::table('cuad_bonos_actividades as ba')
                ->join('actividad_metodos as am', 'am.id', '=', 'ba.metodo_id')
                ->join('actividades as a', 'a.id', '=', 'ba.actividad_id')
                ->join('cuad_detalles_horas as dh', fn($j) => $j->on('dh.registro_diario_id', '=', 'ba.registro_diario_id')->on('dh.codigo_labor', '=', 'a.codigo_labor'))
                ->where('ba.registro_diario_id', $id)->whereNull('am.estandar')->where('am.respeta_jornal', false)
                ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0'));
            DB::table('cuad_registros_diarios')->where('id', $id)->update(['horas_destajo' => round($horas, 2)]);
        }
    }

    /**
     * Ejecuta $guardar en una transacción que se deshace y devuelve los bonos "respeta el jornal" que cambiarían.
     * Sirve para avisar antes de guardar un cambio de jornal.
     */
    public static function simular(callable $guardar, string $desde, string $hasta): array
    {
        DB::beginTransaction();
        try {
            $guardar();
            return self::afectados($desde, $hasta);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Bonos "respeta el jornal" del rango cuyo monto guardado ya no coincide con el jornal actual.
     * @return array<int, array{bono_id:int, registro_id:int, fecha:string, cuadrillero:string, campo:string, labor:string,
     *   horas:float, cantidad:float, jornal:float, antes:float, despues:float, pagado:bool}>
     */
    public static function afectados(string $desde, string $hasta): array
    {
        $bonos = DB::table('cuad_bonos_actividades as b')
            ->join('actividad_metodos as m', 'm.id', '=', 'b.metodo_id')
            ->join('actividades as a', 'a.id', '=', 'b.actividad_id')
            ->join('cuad_registros_diarios as r', 'r.id', '=', 'b.registro_diario_id')
            ->leftJoin('cuad_cuadrilleros as c', 'c.id', '=', 'r.cuadrillero_id')
            ->whereBetween('r.fecha', [Carbon::parse($desde)->toDateString(), Carbon::parse($hasta)->toDateString()])
            ->whereNull('m.estandar')->where('m.respeta_jornal', true)->where('b.bono_manual', false)
            ->get(['b.id as bono_id', 'b.registro_diario_id', 'b.actividad_id', 'b.metodo_id', 'b.total_bono', 'b.esta_pagado',
                'r.fecha', 'r.cuadrillero_id', 'r.costo_personalizado_dia', 'r.jornal_aplicado', 'c.nombres', 'a.campo', 'a.codigo_labor', 'a.nombre_labor']);

        $tramos = DB::table('actividad_metodo_tramos')->whereIn('metodo_id', $bonos->pluck('metodo_id')->unique())
            ->get(['metodo_id', 'hasta', 'monto'])->groupBy('metodo_id');
        $lista = [];
        foreach ($bonos as $b) {
            $horas = (float) DB::table('cuad_detalles_horas')->where('registro_diario_id', $b->registro_diario_id)
                ->where('campo_nombre', $b->campo)->where('codigo_labor', $b->codigo_labor)
                ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin) / 60.0'));
            $cantidad = (float) DB::table('produccion_registros')->where('actividad_id', $b->actividad_id)
                ->where('cuadrillero_id', $b->cuadrillero_id)->where('con_bono', true)->sum('cantidad');
            $jornal = (float) ($b->costo_personalizado_dia ?: $b->jornal_aplicado);
            $despues = $jornal > 0
                ? CuadrillaBonoReglas::bonoRespetaJornal($cantidad, $horas, $jornal, ($tramos[$b->metodo_id] ?? collect())->all())['bono']
                : 0.0;
            if (abs($despues - (float) $b->total_bono) < 0.005) {
                continue;
            }
            $lista[] = ['bono_id' => $b->bono_id, 'registro_id' => $b->registro_diario_id, 'fecha' => Carbon::parse($b->fecha)->toDateString(),
                'cuadrillero' => $b->nombres ?? '-', 'campo' => $b->campo, 'labor' => "{$b->codigo_labor} · {$b->nombre_labor}",
                'horas' => round($horas, 2), 'cantidad' => $cantidad, 'jornal' => $jornal,
                'antes' => (float) $b->total_bono, 'despues' => $despues, 'pagado' => (bool) $b->esta_pagado];
        }
        usort($lista, fn($x, $y) => [$x['fecha'], $x['cuadrillero']] <=> [$y['fecha'], $y['cuadrillero']]);
        return $lista;
    }

    /**
     * Recalcula los bonos "respeta el jornal" del rango con el jornal actual (sin tocar los pagados) y el total de bono
     * de cada registro diario. Devuelve cuántos bonos cambió.
     */
    public static function aplicar(string $desde, string $hasta): int
    {
        $n = 0;
        $registros = [];
        foreach (self::afectados($desde, $hasta) as $a) {
            if ($a['pagado']) {
                continue;
            }
            DB::table('cuad_bonos_actividades')->where('id', $a['bono_id'])->update(['total_bono' => $a['despues'], 'updated_at' => now()]);
            $registros[$a['registro_id']] = true;
            $n++;
        }
        foreach (array_keys($registros) as $id) {
            DB::table('cuad_registros_diarios')->where('id', $id)
                ->update(['total_bono' => DB::table('cuad_bonos_actividades')->where('registro_diario_id', $id)->sum('total_bono')]);
        }
        if ($n) {
            \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio($desde, $hasta);
        }
        return $n;
    }
}
