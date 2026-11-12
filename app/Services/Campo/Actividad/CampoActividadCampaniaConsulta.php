<?php

namespace App\Services\Campo\Actividad;

use App\Models\CampoCampania;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use App\Services\Campania\Sector\CampaniaSectorReglas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Labores de un campo agrupadas por campaña (encabezado vertical) y por grupo de hileras (encabezado horizontal).
 *
 * Cada labor de un día va a la columna del grupo de hileras que ese día está en la etapa de la labor (la misma regla
 * que valida al guardar horas). Si varios grupos están en esa etapa, va en todos marcada como "cualquiera de estos".
 * Las labores sin etapa (culturales, riego, sanidad…) van en "Sin etapa" y las que no caben en ningún grupo en
 * "Fuera de etapa". Los días sin campaña del campo van en un bloque aparte.
 */
class CampoActividadCampaniaConsulta
{
    public const SIN_ETAPA = 'sin_etapa';
    public const FUERA = 'fuera';

    public function __construct(
        private CampoActividadConsulta $actividades,
        private CampaniaSectorConsulta $sectores,
    ) {
    }

    /**
     * @return array [['campania' => ?array{id, nombre, inicio, fin}, 'columnas' => [['clave', 'nombre', 'detalle', 'tipo']],
     *                 'dias' => [['fecha', 'etapas' => [clave => etapa], 'celdas' => [clave => [actividad…]]]], 'totales' => [clave => horas]], …]
     *                actividad = ['codigo', 'labor', 'etapa', 'planilla', 'cuadrilla', 'horas', 'compartida']
     */
    public function bloques(string $campo, string $desde, string $hasta): array
    {
        $labores = DB::table('labores')->whereNotNull('codigo')->get(['codigo', 'nombre_labor', 'codigo_mano_obra'])->keyBy('codigo');
        $porDia = $this->actividades->tramos($desde, $hasta, [$campo])->groupBy('fecha');
        if ($porDia->isEmpty()) {
            return [];
        }

        $campanias = CampoCampania::where('campo', $campo)->whereNotNull('fecha_inicio')
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $desde))
            ->orderByDesc('fecha_inicio')->get();

        $bloques = [];
        $usados = [];
        foreach ($campanias as $c) {
            $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
            $fin = CampaniaSectorConsulta::fin($c);
            $dias = $porDia->filter(fn($t, $fecha) => $fecha >= $inicio && $fecha <= $fin && !isset($usados[$fecha]));
            if ($dias->isEmpty()) {
                continue;
            }
            foreach ($dias->keys() as $f) {
                $usados[$f] = true;
            }
            $sectores = $this->sectores->sectores($c);
            $bloques[] = $this->bloque($dias, $labores, $sectores, [
                'id' => $c->id, 'nombre' => $c->nombre_campania, 'inicio' => $inicio, 'fin' => $c->fecha_fin ? $fin : null,
            ]);
        }

        // Días que no caen en ninguna campaña del campo
        $sueltos = $porDia->reject(fn($t, $fecha) => isset($usados[$fecha]));
        if ($sueltos->isNotEmpty()) {
            $bloques[] = $this->bloque($sueltos, $labores, [], null);
        }
        return $bloques;
    }

    private function bloque($dias, $labores, array $sectores, ?array $campania): array
    {
        $columnas = [];
        foreach ($sectores as $s) {
            $columnas[] = ['clave' => 's' . $s['id'], 'nombre' => $s['nombre'], 'detalle' => "Hileras {$s['hilera_desde']}–{$s['hilera_hasta']}", 'tipo' => 'grupo'];
        }
        $columnas[] = ['clave' => self::SIN_ETAPA, 'nombre' => $sectores ? 'Sin etapa' : 'Labores', 'detalle' => $sectores ? 'Riego, culturales, sanidad…' : ($campania ? 'Campaña sin mapa de etapas' : 'Fuera de campaña'), 'tipo' => 'general'];
        $conFuera = false;

        $filas = [];
        $totales = array_fill_keys(array_column($columnas, 'clave'), 0.0);
        foreach ($dias->sortKeysDesc() as $fecha => $tramos) {
            $etapasDia = [];
            foreach ($sectores as $s) {
                $etapasDia['s' . $s['id']] = CampaniaSectorConsulta::etapaEn($s['etapas'], $fecha);
            }
            $celdas = [];
            foreach ($tramos->groupBy('labor') as $codigo => $g) {
                $l = $labores[$codigo] ?? null;
                $etapa = CampaniaSectorReglas::etapaDeManoObra($l?->codigo_mano_obra);
                $act = [
                    'codigo' => (int) $codigo,
                    'labor' => (string) ($l->nombre_labor ?? ''),
                    'etapa' => $etapa,
                    'planilla' => $g->where('origen', 'planilla')->count(),
                    'cuadrilla' => $g->where('origen', 'cuadrilla')->count(),
                    'horas' => round($g->sum('minutos') / 60, 2),
                    'compartida' => false,
                ];
                if (!$etapa || !$sectores) {
                    $destinos = [self::SIN_ETAPA];
                } else {
                    $familia = CampaniaSectorReglas::familia($etapa);
                    $destinos = collect($etapasDia)->filter(fn($e) => $e && CampaniaSectorReglas::familia($e) === $familia)->keys()->all();
                    if (!$destinos) {
                        $destinos = [self::FUERA];
                        $conFuera = true;
                    }
                }
                $act['compartida'] = count($destinos) > 1;
                foreach ($destinos as $d) {
                    $celdas[$d][] = $act;
                }
                // Las horas cuentan una vez: las compartidas se reparten parejo entre sus grupos
                foreach ($destinos as $d) {
                    $totales[$d] = ($totales[$d] ?? 0) + $act['horas'] / count($destinos);
                }
            }
            foreach ($celdas as &$lista) {
                usort($lista, fn($a, $b) => ($b['planilla'] + $b['cuadrilla']) <=> ($a['planilla'] + $a['cuadrilla']));
            }
            unset($lista);
            $filas[] = ['fecha' => $fecha, 'etapas' => $etapasDia, 'celdas' => $celdas];
        }
        if ($conFuera) {
            $columnas[] = ['clave' => self::FUERA, 'nombre' => 'Fuera de etapa', 'detalle' => 'Ningún grupo estaba en esa etapa', 'tipo' => 'fuera'];
        }

        return [
            'campania' => $campania,
            'columnas' => $columnas,
            'dias' => $filas,
            'totales' => array_map(fn($h) => round($h, 2), $totales),
        ];
    }
}
