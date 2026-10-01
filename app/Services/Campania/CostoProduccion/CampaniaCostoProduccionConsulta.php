<?php

namespace App\Services\Campania\CostoProduccion;

use App\Models\CampaniaCostoProduccion;
use App\Models\CampoCampania;
use App\Models\Labores;
use App\Models\ManoObra;
use App\Models\ResumenCostoDiario;
use App\Support\FormatoHelper;

/**
 * Costos de producción de una campaña (solo lectura).
 *
 * - agrupar(): arma las partidas en vivo desde resumen_costo_diarios (por nombre de campaña y campo).
 * - estructura(): convierte una versión guardada en el reporte por secciones, con montos por ha en US$.
 */
class CampaniaCostoProduccionConsulta
{
    /**
     * Partidas en vivo, en el orden del reporte.
     *
     * @return array{filas: array<int, array{seccion: string, grupo: ?string, item: string, codigo: ?string, cantidad: ?float, costo_soles: float}>,
     *               filas_origen: int, fecha_desde: ?string, fecha_hasta: ?string}
     */
    public function agrupar(CampoCampania $campania): array
    {
        $base = ResumenCostoDiario::query()
            ->where('campania', $campania->nombre_campania)
            ->where('campo', $campania->campo);

        $resumen = (clone $base)->selectRaw('COUNT(*) as filas, MIN(fecha) as desde, MAX(fecha) as hasta')->first();

        $agrupado = (clone $base)
            ->groupBy('origen_tipo', 'labor', 'labor_nombre', 'insumo_nombre')
            ->selectRaw('origen_tipo, labor, labor_nombre, insumo_nombre,
                SUM(costo_total) as costo, SUM(cantidad_jornales) as jornales, SUM(cantidad_insumo) as cantidad_insumo')
            ->get();

        $todasLabores = Labores::get(['codigo', 'nombre_labor', 'codigo_mano_obra']);
        $labores = [
            'codigo' => $todasLabores->keyBy(fn($l) => (string) $l->codigo),
            'nombre' => $todasLabores->keyBy(fn($l) => FormatoHelper::normalizarNombre($l->nombre_labor)),
        ];
        $manoObras = ManoObra::pluck('descripcion', 'codigo');

        $partidas = [];
        foreach ($agrupado as $fila) {
            $seccion = CampaniaCostoProduccionReglas::seccionDeOrigen($fila->origen_tipo);
            [$grupoCodigo, $grupo, $item, $codigo] = $this->clasificar($seccion, $fila, $labores, $manoObras);
            [, , , , $tipoCantidad] = CampaniaCostoProduccionReglas::SECCIONES[$seccion];

            $clave = "{$seccion}|{$grupo}|{$item}";
            $partidas[$clave] ??= [
                'seccion' => $seccion,
                'grupo' => $grupo,
                'grupo_codigo' => $grupoCodigo,
                'item' => $item,
                'codigo' => $codigo,
                'cantidad' => $tipoCantidad ? 0.0 : null,
                'costo_soles' => 0.0,
            ];
            $partidas[$clave]['costo_soles'] += (float) $fila->costo;
            if ($tipoCantidad === 'jornales') {
                $partidas[$clave]['cantidad'] += (float) $fila->jornales;
            } elseif ($tipoCantidad === 'insumo') {
                $partidas[$clave]['cantidad'] += (float) $fila->cantidad_insumo;
            }
        }

        return [
            'filas' => $this->ordenar(array_values($partidas)),
            'filas_origen' => (int) $resumen->filas,
            'fecha_desde' => $resumen->desde,
            'fecha_hasta' => $resumen->hasta,
        ];
    }

    /**
     * Reporte por secciones de una versión guardada. Montos por ha en US$ con el área y el tipo de cambio de la
     * versión; si falta alguno, los montos por ha quedan en null y se avisa.
     */
    public function estructura(CampaniaCostoProduccion $version): array
    {
        $area = (float) $version->area;
        $tc = (float) $version->tipo_cambio;
        $porHa = fn(?float $valor) => $valor === null || $area <= 0 ? null : $valor / $area;
        $dolaresHa = fn(float $soles) => $area > 0 && $tc > 0 ? $soles / $tc / $area : null;

        $secciones = [];
        foreach (CampaniaCostoProduccionReglas::SECCIONES as $clave => [$numero, $titulo, $bloque, , $tipoCantidad]) {
            $secciones[$clave] = [
                'numero' => $numero, 'titulo' => $titulo, 'bloque' => $bloque, 'con_cantidad' => $tipoCantidad !== null,
                'grupos' => [], 'cantidad' => $tipoCantidad ? 0.0 : null, 'costo_soles' => 0.0,
            ];
        }

        foreach ($version->detalles as $d) {
            $s = &$secciones[$d->seccion];
            $grupo = $d->grupo ?? '';
            $s['grupos'][$grupo] ??= ['nombre' => $d->grupo, 'items' => [], 'cantidad' => $s['con_cantidad'] ? 0.0 : null, 'costo_soles' => 0.0];
            $s['grupos'][$grupo]['items'][] = [
                'item' => $d->item,
                'codigo' => $d->codigo,
                'cantidad_ha' => $porHa($d->cantidad),
                'costo_usd_ha' => $dolaresHa($d->costo_soles),
                'costo_soles' => $d->costo_soles,
            ];
            $s['grupos'][$grupo]['costo_soles'] += $d->costo_soles;
            $s['costo_soles'] += $d->costo_soles;
            if ($s['con_cantidad']) {
                $s['grupos'][$grupo]['cantidad'] += (float) $d->cantidad;
                $s['cantidad'] += (float) $d->cantidad;
            }
            unset($s);
        }

        foreach ($secciones as &$s) {
            $s['cantidad_ha'] = $porHa($s['cantidad']);
            $s['costo_usd_ha'] = $dolaresHa($s['costo_soles']);
            foreach ($s['grupos'] as &$g) {
                $g['cantidad_ha'] = $porHa($g['cantidad']);
                $g['costo_usd_ha'] = $dolaresHa($g['costo_soles']);
            }
            unset($g);
        }
        unset($s);

        $totalBloque = fn(string $bloque) => array_sum(array_map(fn($s) => $s['bloque'] === $bloque ? $s['costo_soles'] : 0, $secciones));
        $produccion = $totalBloque(CampaniaCostoProduccionReglas::PRODUCCION);
        $operativo = $totalBloque(CampaniaCostoProduccionReglas::OPERATIVO);
        $fijo = $totalBloque(CampaniaCostoProduccionReglas::FIJO);
        $total = $produccion + $operativo + $fijo;

        return [
            'secciones' => $secciones,
            'totales' => [
                'produccion' => ['soles' => $produccion, 'usd_ha' => $dolaresHa($produccion)],
                'operativo' => ['soles' => $operativo, 'usd_ha' => $dolaresHa($operativo)],
                'fijo' => ['soles' => $fijo, 'usd_ha' => $dolaresHa($fijo)],
                'total' => [
                    'soles' => $total,
                    'usd' => $tc > 0 ? $total / $tc : null,
                    'usd_ha' => $dolaresHa($total),
                ],
            ],
            'avisos' => array_values(array_filter([
                $area <= 0 ? 'La campaña no tiene área: no se pueden calcular los montos por hectárea.' : null,
                $tc <= 0 ? 'La campaña no tiene tipo de cambio: no se pueden calcular los montos en US$.' : null,
                $tc > 0 && $tc <= 1 ? 'El tipo de cambio de la campaña es ' . number_format($tc, 4)
                    . ' (valor por defecto): los montos en US$ salen iguales a los soles. Corrígelo en Datos generales y vuelve a consultar.' : null,
            ])),
        ];
    }

    /** @return array{0: ?string, 1: ?string, 2: string, 3: ?string} [código grupo, grupo, item, código] */
    private function clasificar(string $seccion, object $fila, $labores, $manoObras): array
    {
        if ($seccion === 'mano_obra') {
            $codigo = $fila->labor !== null && $fila->labor !== '' ? (string) $fila->labor : null;
            if ($codigo !== null) {
                $labor = $labores['codigo']->get($codigo);
                $grupoCodigo = $labor?->codigo_mano_obra;
            } else {
                // Sin código de labor = viene del registro de riego (tipo de labor escrito a mano): se busca la labor
                // por nombre y, si no hay, va a "Riego y fertilización" con su nombre original.
                $labor = $labores['nombre']->get(FormatoHelper::normalizarNombre($fila->labor_nombre));
                $grupoCodigo = $labor?->codigo_mano_obra ?: CampaniaCostoProduccionReglas::MANO_OBRA_RIEGO;
            }
            $grupo = $grupoCodigo ? ($manoObras[$grupoCodigo] ?? $grupoCodigo) : CampaniaCostoProduccionReglas::SIN_MANO_OBRA;
            $item = $labor?->nombre_labor ?: ($fila->labor_nombre ?: 'Sin labor');
            return [$grupoCodigo, $grupo, trim($item), $codigo ?? ($labor ? (string) $labor->codigo : null)];
        }

        if (in_array($seccion, ['fertilizante', 'pesticida'], true)) {
            return [null, null, trim($fila->insumo_nombre ?: 'Sin insumo'), null];
        }

        if ($fila->origen_tipo === 'mano_obra_indirecta') {
            return [null, null, CampaniaCostoProduccionReglas::PARTIDA_MOI_PLANILLA, null];
        }

        return [null, null, trim($fila->labor_nombre ?: $fila->insumo_nombre ?: ucfirst(str_replace('_', ' ', $fila->origen_tipo))), null];
    }

    /** Secciones en orden; mano de obra por grupo (orden del ciclo) e ítem; insumos de mayor a menor costo. */
    private function ordenar(array $partidas): array
    {
        $ordenSeccion = array_flip(array_keys(CampaniaCostoProduccionReglas::SECCIONES));
        $ordenGrupo = array_flip(CampaniaCostoProduccionReglas::ORDEN_MANO_OBRA);
        $ordenPartida = array_flip(CampaniaCostoProduccionReglas::ORDEN_PARTIDAS);

        usort($partidas, function ($a, $b) use ($ordenSeccion, $ordenGrupo, $ordenPartida) {
            $porSeccion = $ordenSeccion[$a['seccion']] <=> $ordenSeccion[$b['seccion']];
            if ($porSeccion !== 0) {
                return $porSeccion;
            }
            if ($a['seccion'] === 'mano_obra') {
                $ga = $ordenGrupo[$a['grupo_codigo']] ?? 900;
                $gb = $ordenGrupo[$b['grupo_codigo']] ?? 900;
                return [$ga, $a['grupo'], $a['item']] <=> [$gb, $b['grupo'], $b['item']];
            }
            if (in_array($a['seccion'], ['fertilizante', 'pesticida'], true)) {
                return $b['costo_soles'] <=> $a['costo_soles'];
            }
            // Costo operativo / fijo: orden de los conceptos de /costos/mensual
            $oa = $ordenPartida[mb_strtoupper($a['item'])] ?? 900;
            $ob = $ordenPartida[mb_strtoupper($b['item'])] ?? 900;
            return [$oa, $a['item']] <=> [$ob, $b['item']];
        });

        return array_map(function ($p) {
            unset($p['grupo_codigo']);
            return $p;
        }, $partidas);
    }
}
