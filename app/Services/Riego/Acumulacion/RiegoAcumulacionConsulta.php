<?php

namespace App\Services\Riego\Acumulacion;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use Illuminate\Support\Carbon;

/**
 * Horas acumuladas de riego de un regador (solo lectura).
 *
 * Las horas regadas por encima del jornal se acumulan (banco) y se usan otro día. La BDD de costos lleva las horas
 * reales el día que se regó y no vuelve a costear el día de uso: para que el cierre de costos del mes cuadre con lo
 * pagado, lo acumulado en un mes debe usarse en ese mismo mes.
 */
class RiegoAcumulacionConsulta
{
    /**
     * Situación al inicio de una fecha (lo acumulado ANTES de esa fecha, menos lo usado ANTES de esa fecha). Sirve para
     * responder "¿cuántas horas tengo para usar?" mirando cualquier día, aunque ese día ya tenga su registro.
     *
     * @return array{disponible:int, origenes: array<int, array{fecha:string, acumulado:int, usado_antes:int, disponible:int, usos: array<int, array{fecha:string, minutos:int}>, otro_mes:bool}>}
     */
    public function saldoAl(string $trabajadorType, int $trabajadorId, string $fecha): array
    {
        $f = Carbon::parse($fecha)->toDateString();
        $origenes = ConsolidadoRiego::where('trabajador_type', $trabajadorType)->where('trabajador_id', $trabajadorId)
            ->where('minutos_acumulados', '>', 0)->whereDate('fecha', '<', $f)->orderBy('fecha')->get();
        $usos = AcumulacionUso::with('consolidadoDestino:id,fecha')->whereIn('consolidado_origen_id', $origenes->pluck('id'))->get()
            ->groupBy('consolidado_origen_id');

        $lista = [];
        $total = 0;
        foreach ($origenes as $o) {
            $fo = Carbon::parse($o->fecha)->toDateString();
            $susUsos = ($usos[$o->id] ?? collect())->map(fn($u) => [
                'fecha' => Carbon::parse($u->consolidadoDestino?->fecha)->toDateString(),
                'minutos' => (int) $u->minutos_consumidos,
            ])->sortBy('fecha')->values()->all();
            $usadoAntes = (int) collect($susUsos)->where('fecha', '<', $f)->sum('minutos');
            $disponible = max(0, (int) $o->minutos_acumulados - $usadoAntes);
            $total += $disponible;
            $lista[] = [
                'fecha' => $fo,
                'acumulado' => (int) $o->minutos_acumulados,
                'usado_antes' => $usadoAntes,
                'disponible' => $disponible,
                'usos' => $susUsos,
                // Acumulado en otro mes que la fecha consultada: ya no cuadra con el cierre de su mes
                'otro_mes' => substr($fo, 0, 7) !== substr($f, 0, 7),
            ];
        }
        return ['disponible' => $total, 'origenes' => $lista];
    }

    /**
     * Días del rango en que se usaron más horas acumuladas de las que había (el uso no está cubierto por usos registrados).
     *
     * @return array<int, array{trabajador_type:string, trabajador_id:int, nombre:string, fecha:string, usado:int, cubierto:int}>
     */
    public function usosSinSaldo(string $inicio, string $fin): array
    {
        $usos = \App\Models\ReporteDiarioRiego::where('por_acumulacion', true)->whereBetween('fecha', [$inicio, $fin])->get()
            ->groupBy('consolidado_id');
        $casos = [];
        foreach ($usos as $consolidadoId => $filas) {
            $usado = (int) $filas->sum(fn($r) => Carbon::parse($r->hora_inicio)->diffInMinutes(Carbon::parse($r->hora_fin)));
            $cubierto = (int) AcumulacionUso::where('consolidado_destino_id', $consolidadoId)->sum('minutos_consumidos');
            if ($usado - $cubierto > 5 && ($c = ConsolidadoRiego::find($consolidadoId))) {
                $casos[] = ['trabajador_type' => $c->trabajador_type, 'trabajador_id' => (int) $c->trabajador_id, 'nombre' => $c->trabajador_nombre,
                    'fecha' => Carbon::parse($c->fecha)->toDateString(), 'usado' => $usado, 'cubierto' => $cubierto];
            }
        }
        return $casos;
    }

    /**
     * Por mes: horas acumuladas que no se usaron en el mismo mes (quedan sin usar o se usaron otro mes).
     *
     * @return array<int, array{trabajador_type:string, trabajador_id:int, nombre:string, sin_usar:int, usado_otro_mes:int, dias: string[]}>
     */
    public function fueraDeMes(string $inicio, string $fin): array
    {
        $origenes = ConsolidadoRiego::where('minutos_acumulados', '>', 0)->whereBetween('fecha', [$inicio, $fin])->get();
        $usos = AcumulacionUso::with('consolidadoDestino:id,fecha')->whereIn('consolidado_origen_id', $origenes->pluck('id'))->get()
            ->groupBy('consolidado_origen_id');
        $porTrabajador = [];
        foreach ($origenes as $o) {
            $clave = $o->trabajador_type . '|' . $o->trabajador_id;
            $mes = substr(Carbon::parse($o->fecha)->toDateString(), 0, 7);
            $usado = $otroMes = 0;
            foreach ($usos[$o->id] ?? [] as $u) {
                $usado += (int) $u->minutos_consumidos;
                if (substr(Carbon::parse($u->consolidadoDestino?->fecha)->toDateString(), 0, 7) !== $mes) {
                    $otroMes += (int) $u->minutos_consumidos;
                }
            }
            $sinUsar = max(0, (int) $o->minutos_acumulados - $usado);
            if (!$sinUsar && !$otroMes) {
                continue;
            }
            $porTrabajador[$clave] ??= ['trabajador_type' => $o->trabajador_type, 'trabajador_id' => (int) $o->trabajador_id,
                'nombre' => $o->trabajador_nombre, 'sin_usar' => 0, 'usado_otro_mes' => 0, 'dias' => []];
            $porTrabajador[$clave]['sin_usar'] += $sinUsar;
            $porTrabajador[$clave]['usado_otro_mes'] += $otroMes;
            $porTrabajador[$clave]['dias'][] = Carbon::parse($o->fecha)->format('d/m');
        }
        return array_values($porTrabajador);
    }
}
