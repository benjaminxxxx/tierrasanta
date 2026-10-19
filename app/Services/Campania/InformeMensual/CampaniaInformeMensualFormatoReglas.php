<?php

namespace App\Services\Campania\InformeMensual;

use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas;
use Carbon\Carbon;

/**
 * Convierte los datos congelados de un informe mensual en tablas listas para mostrar. El Excel y la vista web usan
 * las mismas tablas, así que siempre dicen lo mismo.
 *
 * Cada bloque es una tabla independiente con sus propias columnas (sin columnas vacías compartidas entre bloques):
 *   ['clave', 'titulo', 'hoja' => resumen|detalle, 'nota', 'columnas' => [[titulo, tipo]], 'filas' => [['celdas', 'estilo']]]
 * Tipos de columna: texto, numero (2 decimales), cantidad (3), entero, fecha, pct, usd, soles.
 * Estilos de fila: normal, seccion, grupo, total, error, aviso.
 *
 * Los US$/ha se calculan aquí con el área y el tipo de cambio guardados en la ficha del informe.
 */
class CampaniaInformeMensualFormatoReglas
{
    public const HOJA_RESUMEN = 'resumen';
    public const HOJA_DETALLE = 'detalle';
    public const HOJA_EVALUACIONES = 'evaluaciones';
    public const HOJAS = [self::HOJA_RESUMEN => 'Resumen', self::HOJA_DETALLE => 'Detalle', self::HOJA_EVALUACIONES => 'Evaluaciones'];

    /** @return array<int, array> */
    public static function tablas(array $d): array
    {
        $area = (float) ($d['ficha']['area'] ?? 0);
        $tc = (float) ($d['ficha']['tipo_cambio'] ?? 0);
        $usdHa = fn($soles) => $area > 0 && $tc > 0 ? (float) $soles / $tc / $area : null;
        $porHa = fn($v) => $v === null || $area <= 0 ? null : (float) $v / $area;

        return array_values(array_filter([
            self::ficha($d['ficha']),
            self::advertencias($d['advertencias'] ?? []),
            self::etapas($d['etapas']),
            isset($d['avance']) ? self::avance($d['avance']) : null,
            self::evaluaciones($d['evaluaciones']),
            self::infestaciones($d['infestaciones']['resumen']),
            self::cosecha($d['cosecha']['resumen']),
            self::fertilizantes($d['fertilizacion']),
            self::nutrientes($d['fertilizacion']),
            self::sanidad($d['sanidad']['productos']),
            self::riego($d['riego']),
            self::resumenCostos($d['costos'], $usdHa),
            self::costoPorMes($d['costos']['por_mes'] ?? [], $area),
            self::costos($d['costos'], $usdHa, $porHa),
            self::ventasResumen($d['ventas']),
            self::rentabilidad($d['rentabilidad']),
            isset($d['indices']) ? self::indices($d['indices']) : null,
            // Hoja de detalle: las listas largas
            self::detalle('det_infestaciones', 'Infestaciones registradas', ['Fecha' => 'fecha', 'Tipo' => 'texto', 'Método' => 'texto', 'Kg madres' => 'numero', 'Infestadores' => 'entero', 'Campo origen' => 'texto'],
                array_map(fn($r) => [$r['fecha'], ucfirst($r['tipo']), ucfirst((string) $r['metodo']), $r['kg_madres'], $r['infestadores'], $r['origen']], $d['infestaciones']['detalle'])),
            self::detalle('det_cosecha', 'Ingresos de cochinilla de la campaña', ['Fecha' => 'fecha', 'Tipo' => 'texto', 'Lote' => 'texto', 'Recogida en' => 'texto', 'Kg' => 'numero'],
                array_map(fn($r) => [$r['fecha'], $r['tipo'], $r['lote'], $r['recogido_en'] ?? null, $r['kg']], $d['cosecha']['detalle'])),
            self::detalle('det_fertilizantes', 'Salidas de fertilizantes', ['Fecha' => 'fecha', 'Producto' => 'texto', 'Kg' => 'cantidad'],
                array_map(fn($r) => [$r['fecha'], $r['producto'], $r['kg']], $d['fertilizacion']['detalle'])),
            self::detalle('det_sanidad', 'Aplicaciones sanitarias', ['Fecha' => 'fecha', 'Producto' => 'texto', 'Categoría' => 'texto', 'Cantidad' => 'cantidad'],
                array_map(fn($r) => [$r['fecha'], $r['producto'], ucfirst($r['categoria']), $r['cantidad']], $d['sanidad']['detalle'])),
            self::detalle('det_ventas', 'Ventas facturadas', ['Fecha' => 'fecha', 'Factura' => 'texto', 'Tipo' => 'texto', 'Comprador' => 'texto', 'Kg' => 'numero', 'Precio US$' => 'usd', 'Total US$' => 'usd', 'Ac. carmínico %' => 'numero'],
                array_map(fn($r) => [$r['fecha'], $r['factura'], $r['tipo'], $r['comprador'], $r['kg'], $r['precio_usd'], $r['total_usd'], $r['acido_carminico']], $d['ventas'])),
            // Hoja de evaluaciones: el detalle por cama de cada evaluación (destino de los enlaces del resumen)
            ...self::evaluacionesDetalle($d['evaluaciones']),
        ]));
    }

    private static function tabla(string $clave, string $titulo, array $columnas, array $filas, string $hoja = self::HOJA_RESUMEN, ?string $nota = null, string $vacio = 'Sin registros hasta la fecha de corte.'): array
    {
        $cols = [];
        foreach ($columnas as $t => $tipo) {
            $cols[] = ['titulo' => $t, 'tipo' => $tipo];
        }
        $filas = array_map(fn($f) => isset($f['celdas']) ? $f : ['celdas' => $f, 'estilo' => 'normal'], $filas);
        return compact('clave', 'titulo', 'hoja', 'nota', 'vacio') + ['columnas' => $cols, 'filas' => $filas];
    }

    private static function detalle(string $clave, string $titulo, array $columnas, array $filas): array
    {
        return self::tabla($clave, $titulo, $columnas, $filas, self::HOJA_DETALLE);
    }

    private static function f(?string $fecha): ?string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : null;
    }

    // ------------------------------------------------------------------------------------------------ resumen

    private static function ficha(array $f): array
    {
        $fuente = ['mensual' => 'Promedio ponderado de los meses (ver costo por mes)', 'caja' => 'Caja a la fecha de corte', 'campania' => 'Campaña', 'ninguno' => 'Sin tipo de cambio'][$f['tipo_cambio_fuente']] ?? '';
        $pares = [
            ['Campo', $f['campo'], 'Campaña', $f['campania']],
            ['Variedad', $f['variedad'], 'Sistema de cultivo', $f['sistema_cultivo']],
            ['Área (ha)', $f['area'], 'Tipo de informe', $f['es_cierre'] ? 'CIERRE DE CAMPAÑA' : 'Mensual (acumulado)'],
            ['Inicio de campaña', self::f($f['fecha_inicio']), 'Fin de campaña', self::f($f['fecha_fin']) ?? 'En curso'],
            ['Fecha de corte', self::f($f['fecha_corte']), 'Edad de la campaña', $f['edad']],
            ['Días transcurridos', $f['dias'], 'Semanas transcurridas', $f['semanas']],
            ['Tipo de cambio', $f['tipo_cambio'] ?: null, 'Fuente del tipo de cambio', $fuente],
        ];
        return self::tabla('ficha', 'Datos del lote', ['Dato' => 'texto', 'Valor' => 'texto', 'Dato ' => 'texto', 'Valor ' => 'texto'], $pares);
    }

    private static function advertencias(array $avisos): ?array
    {
        if (!$avisos) {
            return null;
        }
        $filas = array_map(fn($a) => ['celdas' => [$a['nivel'] === 'error' ? 'Falta' : 'Revisar', $a['mensaje']], 'estilo' => $a['nivel'] === 'error' ? 'error' : 'aviso'], $avisos);
        return self::tabla('advertencias', 'Advertencias: datos que faltan para que el informe sea válido', ['Nivel' => 'texto', 'Detalle' => 'texto'], $filas);
    }

    private static function etapas(array $e): array
    {
        $filas = array_map(fn($r) => ["{$r['etapa']}. {$r['nombre']}", $r['desde'], $r['hasta'], $r['dias'], $r['estado']], $e['filas']);
        return self::tabla('etapas', 'Etapas de la campaña', ['Etapa' => 'texto', 'Desde' => 'fecha', 'Hasta' => 'fecha', 'Días' => 'entero', 'Estado' => 'texto'], $filas,
            nota: 'Infestación: ' . (self::f($e['infestacion']) ?? '—') . ' · Reinfestación: ' . (self::f($e['reinfestacion']) ?? '—'));
    }

    /** Pantalla de la evaluación, filtrada en su campaña y con la evaluación resaltada. */
    private static function urlEvaluacion(array $r): ?string
    {
        if (empty($r['id']) || !function_exists('route')) {
            return null;
        }
        $campania = $r['campania_id'] ?? null;
        return match ($r['modelo'] ?? null) {
            'poblacion' => route('evaluacion.poblacion_plantas', array_filter(['campania' => $campania, 'evaluacion' => $r['id']])),
            'brotes' => route('evaluacion.brotes', array_filter(['campaniaId' => $campania, 'evaluacion' => $r['id']])),
            default => null,
        };
    }

    /**
     * Una tabla por evaluación en la hoja "Evaluaciones": conteo por cama y la fila del resultado, que es el destino del
     * enlace del resumen (ancla). Se calcula igual que la pantalla de evaluación, para comprobar que coincide.
     *
     * @return array<int, array>
     */
    private static function evaluacionesDetalle(array $ev): array
    {
        $tablas = [];
        foreach ($ev as $r) {
            if (empty($r['camas']) && !isset($r['clave'])) {
                continue;
            }
            $titulo = "{$r['tipo']} – " . self::f($r['fecha']) . ($r['evaluador'] ? " – {$r['evaluador']}" : '');
            $metros = $r['metros_cama_ha'] ?? 0;
            if ($r['modelo'] === 'poblacion') {
                $filas = array_map(fn($c) => [$c['cama'], $c['longitud'], $c['conteo'], $c['por_metro'], $c['por_metro'] !== null ? $c['por_metro'] * $metros : null], $r['camas']);
                $promMetro = collect($r['camas'])->pluck('por_metro')->filter(fn($v) => $v !== null)->avg();
                $filas[] = ['celdas' => ['PROMEDIO', null, collect($r['camas'])->pluck('conteo')->filter(fn($v) => $v !== null)->avg(), $promMetro, $r['valor']],
                    'estilo' => 'total', 'ancla' => $r['clave'], 'ancla_col' => 4];
                $tablas[] = self::tabla("eval_{$r['clave']}", $titulo, ['Cama' => 'entero', 'Longitud (m)' => 'numero', 'Plantas por hilera' => 'numero',
                    'Plantas por metro' => 'cantidad', 'Plantas por ha' => 'numero'], $filas, self::HOJA_EVALUACIONES,
                    nota: "Plantas por ha = plantas por metro × {$metros} metros de cama por ha.");
            } else {
                $filas = array_map(fn($c) => [$c['cama'], $c['longitud'], $c['b2_actual'], $c['b3_actual'], $c['total_ha'], $c['b2_despues'], $c['b3_despues'], $c['total_despues_ha']], $r['camas']);
                $filas[] = ['celdas' => ['PROMEDIO', null, null, null, $r['valor'], null, null, $r['valor_n_dias'] ?? null],
                    'estilo' => 'total', 'ancla' => $r['clave'], 'ancla_col' => 4];
                $tablas[] = self::tabla("eval_{$r['clave']}", $titulo, ['Cama' => 'entero', 'Longitud (m)' => 'numero', '2° piso actual' => 'numero',
                    '3° piso actual' => 'numero', 'Total actual por ha' => 'numero', '2° piso a N días' => 'numero', '3° piso a N días' => 'numero',
                    'Total a N días por ha' => 'numero'], $filas, self::HOJA_EVALUACIONES,
                    nota: "Por ha = brotes ÷ longitud de la cama × {$metros} metros de cama por ha (2° + 3° piso).");
            }
        }
        return $tablas;
    }

    private static function evaluaciones(array $ev): array
    {
        // Cada valor enlaza a su evaluación: en la web a la pantalla de evaluación, en el Excel a la hoja "Evaluaciones"
        $filas = array_map(fn($r) => [
            'celdas' => [$r['tipo'], $r['fecha'], $r['valor'], $r['unidad'], $r['valor_n_dias'] ?? null],
            'estilo' => 'normal',
            'enlaces' => isset($r['clave']) ? [2 => ['url' => self::urlEvaluacion($r), 'ancla' => $r['clave']]] : [],
        ], $ev);
        return self::tabla('evaluaciones', 'Población de plantas y evaluación de brotes', ['Evaluación' => 'texto', 'Fecha' => 'fecha', 'Valor' => 'numero', 'Unidad' => 'texto', 'A los N días' => 'numero'], $filas);
    }

    private static function infestaciones(array $res): array
    {
        $filas = array_map(fn($r) => ["{$r['tipo']} – {$r['metodo']}", $r['desde'], $r['hasta'], $r['kg_madres'], $r['kg_madres_ha'],
            $r['infestadores'], $r['infestadores_ha'], $r['gramos_por_infestador'], $r['origenes']], $res);
        return self::tabla('infestaciones', 'Infestación y reinfestación', ['Tipo – método' => 'texto', 'Desde' => 'fecha', 'Hasta' => 'fecha', 'Kg madres' => 'numero', 'Kg madres/ha' => 'numero',
            'Infestadores' => 'entero', 'Infestadores/ha' => 'entero', 'g madre/infestador' => 'numero', 'Campos de origen' => 'texto'], $filas);
    }

    private static function cosecha(array $res): array
    {
        $filas = array_map(fn($r) => [$r['tipo'], $r['ingresos'], $r['recogido_en'] ?? null, $r['desde'], $r['hasta'], $r['kg'], $r['kg_ha'],
            $r['es_mama'] ? 'Mamá (vuelve a infestar)' : ($r['es_vendible'] ? 'Vendible' : 'No vendible')], $res);
        if ($res) {
            $filas[] = ['celdas' => ['TOTAL', array_sum(array_column($res, 'ingresos')), null, null, null, array_sum(array_column($res, 'kg')),
                array_sum(array_map(fn($r) => (float) $r['kg_ha'], $res)), null], 'estilo' => 'total'];
        }
        return self::tabla('cosecha', 'Cochinilla producida por la campaña (cosecha de madres y cosecha)', ['Tipo de ingreso' => 'texto', 'Ingresos' => 'entero', 'Recogida en' => 'texto',
            'Desde' => 'fecha', 'Hasta' => 'fecha', 'Kg' => 'numero', 'Kg/ha' => 'numero', 'Uso' => 'texto'], $filas,
            nota: 'Kilos de esta campaña aunque se hayan recogido después o en otro campo (infestadores con su mamá). Los costos de recojo, vaciado y post-cosecha posteriores al cierre van a la campaña que esté vigente en ese momento.');
    }

    private static function fertilizantes(array $fert): array
    {
        $filas = array_map(fn($p) => [$p['producto'], $p['aplicaciones'], $p['kg'], $p['kg_ha'], $p['etapas_kg_ha'][1], $p['etapas_kg_ha'][2], $p['etapas_kg_ha'][3]], $fert['productos']);
        return self::tabla('fertilizantes', 'Fertilización (acumulado)', ['Producto' => 'texto', 'Aplicaciones' => 'entero', 'Kg lote' => 'numero', 'Kg/ha' => 'numero',
            'Etapa 1 kg/ha' => 'numero', 'Etapa 2 kg/ha' => 'numero', 'Etapa 3 kg/ha' => 'numero'], $filas);
    }

    private static function nutrientes(array $fert): ?array
    {
        if (!$fert['productos']) {
            return null;
        }
        $cols = ['Producto' => 'texto'];
        foreach (CampaniaInformeMensualReglas::NUTRIENTES as $n) {
            $cols["{$n} kg/ha"] = 'numero';
        }
        $filas = array_map(fn($p) => array_merge([$p['producto']], array_values($p['nutrientes_kg_ha'])), $fert['productos']);
        $filas[] = ['celdas' => array_merge(['TOTAL'], array_values($fert['nutrientes_total_kg_ha'])), 'estilo' => 'total'];
        return self::tabla('nutrientes', 'Nutrientes aportados (kg/ha)', $cols, $filas,
            nota: 'Según el % de cada nutriente registrado en el producto (Almacén → Productos).');
    }

    private static function sanidad(array $productos): array
    {
        $filas = array_map(fn($p) => [$p['producto'], $p['categoria'], $p['ingrediente'], $p['aplicaciones'], $p['primera'], $p['ultima'], $p['cantidad'], $p['cantidad_ha']], $productos);
        return self::tabla('sanidad', 'Herbicidas, pesticidas y otras aplicaciones', ['Producto' => 'texto', 'Categoría' => 'texto', 'Ingrediente activo' => 'texto', 'Aplicaciones' => 'entero',
            'Primera' => 'fecha', 'Última' => 'fecha', 'Cantidad' => 'cantidad', 'Cantidad/ha' => 'cantidad'], $filas);
    }

    private static function riego(array $r): array
    {
        $filas = array_map(fn($f) => ["{$f['etapa']}. {$f['nombre']}", $f['dias'], $f['registros'], $f['horas'], $f['m3_ha']], $r['filas']);
        $filas[] = ['celdas' => ['TOTAL', array_sum(array_column($r['filas'], 'dias')), array_sum(array_column($r['filas'], 'registros')),
            array_sum(array_column($r['filas'], 'horas')), array_sum(array_column($r['filas'], 'm3_ha'))], 'estilo' => 'total'];
        return self::tabla('riego', 'Riego', ['Etapa' => 'texto', 'Días con riego' => 'entero', 'Registros' => 'entero', 'Horas' => 'numero', 'm³/ha' => 'numero'], $filas,
            nota: 'Descarga: ' . $r['descarga_m3_ha_hora'] . ' m³/ha/hora' . ($r['descarga_por_defecto'] ? ' (valor por defecto: la campaña no tiene una registrada)' : '') . '.');
    }

    /** Totales por sección y grupo con su % del total (lo que antes era "Resumen de costos"). */
    private static function resumenCostos(array $c, callable $usdHa): array
    {
        $total = (float) $c['total_soles'];
        $pct = fn($s) => $total > 0 ? (float) $s / $total : null;
        $filas = [];
        foreach (self::bloques($c) as $bloque => $secciones) {
            $sumaBloque = 0.0;
            // Bloques de una sola sección (operativo, fijo): solo la fila del bloque, sin repetir el nombre
            $unaSeccion = count($secciones) === 1 && $bloque !== CampaniaCostoProduccionReglas::PRODUCCION;
            foreach ($secciones as $s) {
                $suma = array_sum(array_column($s['partidas'], 'costo_soles'));
                $sumaBloque += $suma;
                if ($unaSeccion) {
                    continue;
                }
                $etapas = array_map(fn($n) => $usdHa(array_sum(array_map(fn($p) => $p['etapas_soles'][$n], $s['partidas']))), [1, 2, 3]);
                $filas[] = ['celdas' => array_merge([trim("{$s['numero']} {$s['titulo']}")], [$usdHa($suma), $pct($suma)], $etapas, [$suma]), 'estilo' => 'grupo'];
            }
            $etapasBloque = array_map(fn($n) => $usdHa(array_sum(array_map(fn($s) => array_sum(array_map(fn($p) => $p['etapas_soles'][$n], $s['partidas'])), $secciones))), [1, 2, 3]);
            $filas[] = ['celdas' => array_merge([self::nombreBloque($bloque)], [$usdHa($sumaBloque), $pct($sumaBloque)], $etapasBloque, [$sumaBloque]), 'estilo' => 'seccion'];
        }
        $etapasTotal = array_map(fn($n) => $usdHa(array_sum(array_map(fn($s) => array_sum(array_map(fn($p) => $p['etapas_soles'][$n], $s['partidas'])), $c['secciones']))), [1, 2, 3]);
        $filas[] = ['celdas' => array_merge(['TOTAL COSTO POR HA'], [$usdHa($total), $total > 0 ? 1.0 : null], $etapasTotal, [$total]), 'estilo' => 'total'];

        return self::tabla('resumen_costos', 'Resumen de costos por hectárea', ['Rubro' => 'texto', 'US$/ha' => 'usd', '%' => 'pct',
            'Etapa 1 US$/ha' => 'usd', 'Etapa 2 US$/ha' => 'usd', 'Etapa 3 US$/ha' => 'usd', 'Total S/' => 'soles'], $filas,
            nota: 'Costos de la BDD de costos desde el inicio de la campaña hasta la fecha de corte' . ($c['fecha_desde'] ? ' (registros del ' . self::f($c['fecha_desde']) . ' al ' . self::f($c['fecha_hasta']) . ')' : '') . '.');
    }

    /** Costo de cada mes con su tipo de cambio y el acumulado por ha (columnas mensuales de "Lotes en producción"). */
    private static function costoPorMes(array $meses, float $area): ?array
    {
        if (!$meses) {
            return null;
        }
        $acumulado = 0.0;
        $filas = [];
        foreach ($meses as $m) {
            $usdHa = $area > 0 && $m['usd'] !== null ? $m['usd'] / $area : null;
            $acumulado += (float) $usdHa;
            $filas[] = [ucfirst(Carbon::parse($m['periodo'] . '-01')->locale('es')->isoFormat('MMMM YYYY')), $m['soles'], $m['tipo_cambio'], $m['usd'], $usdHa, $acumulado];
        }
        $soles = array_sum(array_column($meses, 'soles'));
        $usd = array_sum(array_column($meses, 'usd'));
        $filas[] = ['celdas' => ['TOTAL', $soles, $usd > 0 ? $soles / $usd : null, $usd, $area > 0 ? $usd / $area : null, null], 'estilo' => 'total'];
        return self::tabla('costo_mes', 'Costo por mes', ['Mes' => 'texto', 'Costo S/' => 'soles', 'T/C' => 'cantidad', 'Costo US$' => 'usd', 'US$/ha del mes' => 'usd', 'US$/ha acumulado' => 'usd'], $filas,
            nota: 'Cada mes se convierte a dólares con su tipo de cambio (promedio de Caja en el mes).');
    }

    /** Detalle de costos: sección → grupo → partida, con cantidad/ha y US$/ha total y por etapa. */
    private static function costos(array $c, callable $usdHa, callable $porHa): array
    {
        $filas = [];
        $fila = function (string $nombre, ?string $unidad, ?float $cant, float $soles, array $etapasSoles, array $etapasCant, string $estilo) use (&$filas, $usdHa, $porHa) {
            $filas[] = ['celdas' => [
                $nombre, $unidad,
                $cant !== null ? $porHa($cant) : null, $usdHa($soles),
                $cant !== null ? $porHa($etapasCant[1]) : null, $usdHa($etapasSoles[1]),
                $cant !== null ? $porHa($etapasCant[2]) : null, $usdHa($etapasSoles[2]),
                $cant !== null ? $porHa($etapasCant[3]) : null, $usdHa($etapasSoles[3]),
                $soles,
            ], 'estilo' => $estilo];
        };
        $sumar = fn(array $partidas, string $campo, ?int $n = null) => array_sum(array_map(fn($p) => $n ? (float) $p[$campo][$n] : (float) $p[$campo], $partidas));
        $etapas = fn(array $partidas, string $campo) => [1 => $sumar($partidas, $campo, 1), 2 => $sumar($partidas, $campo, 2), 3 => $sumar($partidas, $campo, 3)];

        foreach ($c['secciones'] as $s) {
            $unidad = ['jornales' => 'Jornal', 'insumo' => 'Kg/Lt'][$s['unidad']] ?? null;
            $conCant = $s['unidad'] !== null;
            $fila(trim("{$s['numero']} {$s['titulo']}"), $unidad, $conCant ? $sumar($s['partidas'], 'cantidad') : null, $sumar($s['partidas'], 'costo_soles'),
                $etapas($s['partidas'], 'etapas_soles'), $etapas($s['partidas'], 'etapas_cantidad'), 'seccion');
            $grupos = [];
            foreach ($s['partidas'] as $p) {
                $grupos[$p['grupo'] ?? ''][] = $p;
            }
            foreach ($grupos as $grupo => $partidas) {
                if ($grupo !== '' && count($grupos) > 1 || $grupo !== '' && $s['clave'] === 'mano_obra') {
                    $fila($grupo, $unidad, $conCant ? $sumar($partidas, 'cantidad') : null, $sumar($partidas, 'costo_soles'),
                        $etapas($partidas, 'etapas_soles'), $etapas($partidas, 'etapas_cantidad'), 'grupo');
                }
                foreach ($partidas as $p) {
                    $fila('   ' . $p['item'], $unidad, $conCant ? (float) $p['cantidad'] : null, (float) $p['costo_soles'], $p['etapas_soles'], $p['etapas_cantidad'], 'normal');
                }
            }
        }
        $todas = array_merge(...array_map(fn($s) => $s['partidas'], $c['secciones'] ?: [['partidas' => []]]));
        $fila('TOTAL CAMPAÑA', null, null, (float) $c['total_soles'], $etapas($todas, 'etapas_soles'), [1 => 0, 2 => 0, 3 => 0], 'total');

        return self::tabla('costos', 'Costos de producción por hectárea (detalle)', ['Rubro' => 'texto', 'Unidad' => 'texto', 'Cant./ha' => 'numero', 'US$/ha' => 'usd',
            'Etapa 1 cant./ha' => 'numero', 'Etapa 1 US$/ha' => 'usd', 'Etapa 2 cant./ha' => 'numero', 'Etapa 2 US$/ha' => 'usd',
            'Etapa 3 cant./ha' => 'numero', 'Etapa 3 US$/ha' => 'usd', 'Total S/' => 'soles'], $filas);
    }

    private static function ventasResumen(array $ventas): array
    {
        $filas = [];
        foreach (collect($ventas)->groupBy(fn($v) => $v['tipo'] ?: 'Sin tipo') as $tipo => $g) {
            $kg = $g->sum('kg');
            $filas[] = [$tipo, $g->count(), $kg, $kg > 0 ? $g->sum('total_usd') / $kg : null, $g->sum('total_usd')];
        }
        if ($filas) {
            $kg = array_sum(array_column($ventas, 'kg'));
            $usd = array_sum(array_column($ventas, 'total_usd'));
            $filas[] = ['celdas' => ['TOTAL', count($ventas), $kg, $kg > 0 ? $usd / $kg : null, $usd], 'estilo' => 'total'];
        }
        return self::tabla('ventas', 'Ventas', ['Tipo' => 'texto', 'Facturas' => 'entero', 'Kg' => 'numero', 'Precio promedio US$/kg' => 'usd', 'Ingreso US$' => 'usd'], $filas,
            vacio: 'Sin ventas facturadas hasta la fecha de corte.');
    }

    /** Avance del periodo vegetativo y edad por etapa (proyectada vs acumulada), como el encabezado del Excel. */
    private static function avance(array $a): array
    {
        $filas = [];
        foreach (CampaniaInformeMensualReglas::ETAPAS as $n => $nombre) {
            $filas[] = ["{$n}. {$nombre}", $a['meses_proyectados'][$n], $a['meses_acumulados_etapa'][$n]];
        }
        $filas[] = ['celdas' => ['TOTAL', $a['meses_proyectados_total'], $a['meses_acumulados']], 'estilo' => 'total'];
        $filas[] = ['celdas' => ['Duración proyectada (semanas)', $a['semanas_proyectadas'], $a['semanas_transcurridas']], 'estilo' => 'grupo'];
        $filas[] = ['celdas' => ['AVANCE DEL PERIODO VEGETATIVO (%)', null, $a['avance_pct']], 'estilo' => 'seccion'];
        return self::tabla('avance', 'Avance del periodo vegetativo', ['Etapa' => 'texto', 'Proyectado (meses)' => 'numero', 'Acumulado (meses)' => 'numero'], $filas,
            nota: 'Avance = semanas transcurridas / semanas proyectadas. 1ª etapa proyectada = ' . ($a['etapa1_real'] ? 'meses completos hasta la infestación' : 'valor configurado (aún sin infestación)')
                . '; 2ª y 3ª = valores de Sistema → Configuración. La última fila muestra semanas proyectadas / transcurridas.');
    }

    /** Índices productivos de evaluación por etapa. */
    private static function indices(array $i): array
    {
        $fila = fn(string $nombre, array $porEtapa, ?float $total = null, string $estilo = 'normal') => ['celdas' => array_merge([$nombre], [$porEtapa[1] ?? null, $porEtapa[2] ?? null, $porEtapa[3] ?? null], [$total]), 'estilo' => $estilo];
        $suma = fn(array $v) => array_sum(array_map('floatval', array_filter($v, fn($x) => $x !== null)));
        $filas = [
            $fila('PRODUCCIÓN DE PENCAS EN FUNCIÓN DEL NITRÓGENO', [], null, 'seccion'),
            $fila('Nitrógeno aplicado (kg/ha)', $i['nitrogeno_kg_ha'], $suma($i['nitrogeno_kg_ha'])),
            $fila('Pencas (brotes aptos 2°+3° piso/ha, última evaluación de la etapa)', $i['pencas_ha']),
            $fila('Gramos de nitrógeno por penca', $i['gramos_n_por_penca']),
            $fila('Agua (m³/ha)', $i['agua_m3_ha'], $suma($i['agua_m3_ha'])),
            $fila('PRODUCCIÓN DE COCHINILLA', [], null, 'seccion'),
            $fila('Cochinilla vendible cosechada (kg/ha)', [], $i['cochinilla_kg_ha']),
            $fila('Kg de nitrógeno por kg de cochinilla', [], $i['kg_n_por_kg_cochinilla']),
            $fila('Giberelina (cantidad/ha)', $i['giberelina_ha'], $suma($i['giberelina_ha'])),
            $fila('Dropp (cantidad/ha)', $i['dropp_ha'], $suma($i['dropp_ha'])),
        ];
        if ($i['infestadores']) {
            $filas[] = $fila('POR INFESTADOR', [], null, 'seccion');
            foreach ($i['infestadores'] as $inf) {
                $filas[] = $fila("{$inf['nombre']}: g de madre por infestador", [], $inf['gramos_madre']);
                if ($inf['gramos_produccion'] !== null) {
                    $filas[] = $fila("{$inf['nombre']}: g de cochinilla producida por infestador", [], $inf['gramos_produccion']);
                }
            }
        }
        return self::tabla('indices', 'Índices productivos de evaluación', ['Índice' => 'texto', 'Etapa 1' => 'numero', 'Etapa 2' => 'numero', 'Etapa 3' => 'numero', 'Total' => 'numero'], $filas,
            nota: 'Pencas: de las evaluaciones de brotes registradas. Giberelina y Dropp: productos de sanidad cuyo nombre los contiene. Cochinilla: kilos de tipos vendibles (sin la mamá que vuelve a infestar).');
    }

    private static function rentabilidad(array $r): array
    {
        $ha = $r['por_ha'] ?? [];
        $t = $r['total'];
        $filas = [
            ['Costo total de producción (US$)', $ha['costo_usd'] ?? null, $t['costo_usd']],
            ['Producción vendible (kg)', $ha['produccion_kg'] ?? null, $t['produccion_kg']],
            ['Costo unitario (US$/kg)', $ha['costo_kg_usd'] ?? null, $t['costo_kg_usd']],
            ['Precio de venta promedio (US$/kg)', $ha['precio_kg_usd'] ?? null, $t['precio_kg_usd']],
            ['Ingreso total (US$)', $ha['ingreso_usd'] ?? null, $t['ingreso_usd']],
            ['Utilidad / pérdida bruta (US$)', $ha['utilidad_usd'] ?? null, $t['utilidad_usd']],
            ['Rentabilidad sobre el costo (%)', isset($ha['rentabilidad_pct']) ? $ha['rentabilidad_pct'] / 100 : null, $t['rentabilidad_pct'] !== null ? $t['rentabilidad_pct'] / 100 : null],
        ];
        return self::tabla('rentabilidad', 'Análisis de rentabilidad', ['Parámetro' => 'texto', 'Por hectárea' => 'numero', 'Total' => 'numero'], $filas,
            nota: 'Producción vendible: kilos ingresados de tipos vendibles (sin la mamá que vuelve a infestar). Sin ventas, el precio, la utilidad y la rentabilidad quedan vacíos.');
    }

    /** Secciones agrupadas por bloque (producción, operativo, fijo). */
    private static function bloques(array $c): array
    {
        $bloques = [];
        foreach ($c['secciones'] as $s) {
            $bloques[$s['bloque']][] = $s;
        }
        return $bloques;
    }

    private static function nombreBloque(string $bloque): string
    {
        return [
            CampaniaCostoProduccionReglas::PRODUCCION => 'COSTO DIRECTO (producción)',
            CampaniaCostoProduccionReglas::OPERATIVO => 'COSTO OPERATIVO',
            CampaniaCostoProduccionReglas::FIJO => 'COSTO FIJO',
        ][$bloque] ?? mb_strtoupper($bloque);
    }
}
