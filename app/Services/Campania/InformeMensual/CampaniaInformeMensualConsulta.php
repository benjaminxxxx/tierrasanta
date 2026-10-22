<?php

namespace App\Services\Campania\InformeMensual;

use App\Models\CajaTipoCambio;
use App\Models\CampaniaInformeMensual;
use App\Models\CampoCampania;
use App\Models\EvalBrotesPorPiso;
use App\Models\EvalPoblacionPlanta;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionConsulta;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Arma el informe mensual de una campaña (solo lectura): todo lo acumulado desde el inicio hasta la fecha de corte.
 * El resultado es un arreglo plano (se guarda congelado en campania_informes_mensuales.datos).
 *
 * Montos en soles tal como están en la BDD de costos; los US$/ha se calculan al mostrar con el área y el tipo de
 * cambio guardados en la ficha del informe.
 */
class CampaniaInformeMensualConsulta
{
    public function __construct(private CampaniaCostoProduccionConsulta $costos)
    {
    }

    public function construir(CampoCampania $campania, int $anio, int $mes, ?string $corteMaximo = null): array
    {
        $corte = CampaniaInformeMensualReglas::fechaCorte($campania, $anio, $mes)->toDateString();
        if ($corteMaximo && $corteMaximo < $corte) {
            $corte = $corteMaximo; // informe al día (mes en curso)
        }
        $inicio = Carbon::parse($campania->fecha_inicio)->toDateString();
        $area = (float) $campania->area;

        $infestacion = $this->fechaEvento($campania, 'infestacion', $campania->infestacion_fecha, $corte);
        $reinfestacion = $this->fechaEvento($campania, 'reinfestacion', $campania->reinfestacion_fecha, $corte);
        $rangos = CampaniaInformeMensualReglas::rangosEtapas($inicio, $infestacion, $reinfestacion, $corte);
        [$tc, $tcFuente, $tcFecha] = $this->tipoCambio($campania, $corte);
        // Cochinilla y ventas de una campaña cerrada siguen llegando después del cierre (infestadores con su mamá,
        // ventas hasta 100 días después): en el informe de cierre se cuentan hasta hoy, no hasta la fecha de fin.
        $corteProduccion = CampaniaInformeMensualReglas::esCierre($campania, $anio, $mes) ? max($corte, now()->toDateString()) : $corte;

        $datos = [
            'ficha' => $this->ficha($campania, $anio, $mes, $inicio, $corte, $area, $tc, $tcFuente) + ['tipo_cambio_fecha' => $tcFecha],
            'etapas' => $this->etapas($rangos, $infestacion, $reinfestacion, CampaniaInformeMensualReglas::esCierre($campania, $anio, $mes)),
            'evaluaciones' => $this->evaluaciones($campania, $corte),
            'infestaciones' => $this->infestaciones($campania, $corte, $area),
            'cosecha' => $this->cosecha($campania, $corteProduccion, $area),
            'fertilizacion' => $this->fertilizacion($campania, $inicio, $corte, $area, $rangos),
            'sanidad' => $this->sanidad($campania, $inicio, $corte, $area),
            'riego' => $this->riego($campania, $rangos),
            'costos' => $this->costos($campania, $inicio, $corte, $rangos),
            'ventas' => $this->ventas($campania, $corteProduccion),
        ];
        // US$ mes a mes (cada mes con su tipo de cambio, como el Excel histórico). El tipo de cambio del informe es el
        // promedio ponderado resultante: así las partidas en US$ suman lo mismo que la suma de los meses.
        $datos['costos']['por_mes'] = $this->costoPorMes($campania, $inicio, $corte, $tc);
        $soles = array_sum(array_column($datos['costos']['por_mes'], 'soles'));
        $usd = array_sum(array_column($datos['costos']['por_mes'], 'usd'));
        $datos['ficha']['tipo_cambio_corte'] = $tc;
        if ($usd > 0 && abs($soles) > 0.005) {
            $tc = round($soles / $usd, 6);
            $datos['ficha']['tipo_cambio'] = $tc;
            $datos['ficha']['tipo_cambio_fuente'] = 'mensual';
        }
        $datos['rentabilidad'] = $this->rentabilidad($datos, $area, $tc);
        $datos['avance'] = CampaniaInformeMensualReglas::avanceVegetativo($inicio, $infestacion, $corte);
        $datos['indices'] = $this->indices($campania, $datos, $rangos, $area);
        $datos['advertencias'] = $this->advertencias($campania, $datos, $anio, $mes, $inicio, $corte);
        return $datos;
    }

    /**
     * Informe "al día" sin guardar: campaña abierta → hasta hoy (mes actual); cerrada → informe final al cierre.
     * Devuelve un modelo sin persistir, para mostrarlo o bajarlo en Excel igual que uno guardado.
     */
    public function enVivo(CampoCampania $campania): CampaniaInformeMensual
    {
        $referencia = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin) : now();
        [$anio, $mes] = [$referencia->year, $referencia->month];
        // Campaña abierta: corte hoy (no fin de mes)
        $datos = $this->construir($campania, $anio, $mes, $campania->fecha_fin ? null : now()->toDateString());
        $advertencias = $datos['advertencias'];
        unset($datos['advertencias']);
        $advertencias = array_values(array_filter($advertencias, fn($a) => $a['tipo'] !== 'secuencia'));

        return (new CampaniaInformeMensual())->forceFill([
            'campo_campania_id' => $campania->id,
            'anio' => $anio,
            'mes' => $mes,
            'fecha_desde' => $datos['ficha']['fecha_inicio'],
            'fecha_corte' => $datos['ficha']['fecha_corte'],
            'es_cierre' => (bool) $datos['ficha']['es_cierre'],
            'version' => 0,
            'datos' => $datos,
            'advertencias' => $advertencias,
            'total_soles' => $datos['costos']['total_soles'],
        ]);
    }

    // ------------------------------------------------------------------------------------------------ bloques

    private function ficha(CampoCampania $c, int $anio, int $mes, string $inicio, string $corte, float $area, float $tc, string $tcFuente): array
    {
        $desde = Carbon::parse($inicio);
        $hasta = Carbon::parse($corte);
        $edad = $desde->diff($hasta->copy()->addDay());
        return [
            'campo' => $c->campo,
            'campania' => $c->nombre_campania,
            'variedad' => $c->variedad_tuna,
            'sistema_cultivo' => $c->sistema_cultivo,
            'area' => $area,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $c->fecha_fin ? Carbon::parse($c->fecha_fin)->toDateString() : null,
            'periodo' => sprintf('%04d-%02d', $anio, $mes),
            'fecha_corte' => $corte,
            'es_cierre' => CampaniaInformeMensualReglas::esCierre($c, $anio, $mes),
            'dias' => $desde->diffInDays($hasta) + 1,
            'semanas' => round(($desde->diffInDays($hasta) + 1) / 7, 2),
            'meses' => round($desde->diffInMonths($hasta->copy()->addDay()), 2),
            'edad' => "{$edad->y} año(s), {$edad->m} mes(es), {$edad->d} día(s)",
            'tipo_cambio' => $tc,
            'tipo_cambio_fuente' => $tcFuente,
        ];
    }

    private function etapas(array $rangos, ?string $infestacion, ?string $reinfestacion, bool $esCierre): array
    {
        $filas = [];
        foreach (CampaniaInformeMensualReglas::ETAPAS as $n => $nombre) {
            $r = $rangos[$n];
            $filas[] = [
                'etapa' => $n,
                'nombre' => $nombre,
                'desde' => $r['desde'] ?? null,
                'hasta' => $r['hasta'] ?? null,
                'dias' => $r ? Carbon::parse($r['desde'])->diffInDays(Carbon::parse($r['hasta'])) + 1 : null,
                'estado' => !$r ? ($esCierre ? 'No se realizó' : 'Sin iniciar') : ($r['en_curso'] && !$esCierre ? 'En curso' : 'Terminada'),
            ];
        }
        return ['filas' => $filas, 'infestacion' => $infestacion, 'reinfestacion' => $reinfestacion];
    }

    private function evaluaciones(CampoCampania $c, string $corte): array
    {
        // Cada evaluación lleva su id, de qué pantalla viene (modelo) y el detalle por cama: así el informe enlaza a la
        // pantalla de la evaluación y el Excel a su hoja "Evaluaciones", y se puede comprobar de dónde sale el valor.
        $poblacion = EvalPoblacionPlanta::with('detalles')->where('campania_id', $c->id)->get()
            ->flatMap(function ($e) use ($corte, $c) {
                $etapa = fn(string $tipo, string $clave, $fecha, $valor, string $campoHilera, string $campoMetro) => [
                    'id' => $e->id, 'campania_id' => $c->id, 'modelo' => 'poblacion', 'clave' => "poblacion-{$e->id}-{$clave}",
                    'tipo' => $tipo, 'fecha' => Carbon::parse($fecha)->toDateString(),
                    'valor' => $valor !== null ? round((float) $valor, 2) : null, 'unidad' => 'plantas/ha',
                    'evaluador' => $e->evaluador, 'metros_cama_ha' => (float) $e->metros_cama_ha,
                    'camas' => $e->detalles->sortBy('numero_cama')->map(fn($d) => [
                        'cama' => $d->numero_cama, 'longitud' => (float) $d->longitud_cama,
                        'conteo' => $d->{$campoHilera} !== null ? (float) $d->{$campoHilera} : null,
                        'por_metro' => $d->{$campoMetro} !== null ? round((float) $d->{$campoMetro}, 4) : null,
                    ])->values()->all(),
                ];
                return array_filter([
                    $e->fecha_eval_cero && $e->fecha_eval_cero <= $corte
                        ? $etapa('Población día cero', 'cero', $e->fecha_eval_cero, $e->promedio_plantas_ha_cero, 'eval_cero_plantas_x_hilera', 'plantas_por_metro_cero') : null,
                    $e->fecha_eval_resiembra && $e->fecha_eval_resiembra <= $corte
                        ? $etapa('Población después de resiembra', 'resiembra', $e->fecha_eval_resiembra, $e->promedio_plantas_ha_resiembra, 'eval_resiembra_plantas_x_hilera', 'plantas_por_metro_resiembra') : null,
                ]);
            });
        $brotes = EvalBrotesPorPiso::with('detalles')->where('campania_id', $c->id)->where('fecha', '<=', $corte)->orderBy('fecha')->get()
            ->map(fn($e) => [
                'id' => $e->id, 'campania_id' => $c->id, 'modelo' => 'brotes', 'clave' => "brotes-{$e->id}",
                'tipo' => 'Brotes aptos 2° y 3° piso', 'fecha' => Carbon::parse($e->fecha)->toDateString(),
                'valor' => $e->promedio_actual_total_brotes_2y3piso, 'unidad' => 'brotes/ha',
                'valor_n_dias' => $e->promedio_total_brotes_2y3piso_n_dias,
                'evaluador' => $e->evaluador, 'metros_cama_ha' => (float) $e->metros_cama_ha,
                'camas' => $e->detalles->sortBy('numero_cama')->map(fn($d) => [
                    'cama' => $d->numero_cama, 'longitud' => (float) $d->longitud_cama,
                    'b2_actual' => (float) $d->brotes_aptos_2p_actual, 'b2_despues' => (float) $d->brotes_aptos_2p_despues_n_dias,
                    'b3_actual' => (float) $d->brotes_aptos_3p_actual, 'b3_despues' => (float) $d->brotes_aptos_3p_despues_n_dias,
                    'total_ha' => round((float) $d->total_actual_por_mt, 2), 'total_despues_ha' => round((float) $d->total_despues_por_mt, 2),
                ])->values()->all(),
            ]);
        return $poblacion->concat($brotes)->sortBy('fecha')->values()->all();
    }

    private function infestaciones(CampoCampania $c, string $corte, float $area): array
    {
        // infestadores no es columna: envases × capacidad (igual que CochinillaInfestacion::getInfestadoresAttribute)
        $filas = DB::table('cochinilla_infestaciones')->where('campo_campania_id', $c->id)->where('fecha', '<=', $corte)
            ->selectRaw('cochinilla_infestaciones.*, numero_envases * capacidad_envase AS infestadores')
            ->orderBy('fecha')->get();
        $resumen = [];
        foreach (['infestacion' => 'Infestación', 'reinfestacion' => 'Reinfestación'] as $tipo => $nombre) {
            $grupo = $filas->where('tipo_infestacion', $tipo);
            if ($grupo->isEmpty()) {
                continue;
            }
            foreach ($grupo->groupBy('metodo') as $metodo => $porMetodo) {
                $kg = (float) $porMetodo->sum('kg_madres');
                $infestadores = (float) $porMetodo->sum('infestadores');
                $resumen[] = [
                    'tipo' => $nombre,
                    'metodo' => ucfirst((string) $metodo),
                    'desde' => $porMetodo->min('fecha'),
                    'hasta' => $porMetodo->max('fecha'),
                    'kg_madres' => round($kg, 2),
                    'kg_madres_ha' => $area > 0 ? round($kg / $area, 2) : null,
                    'infestadores' => $infestadores,
                    'infestadores_ha' => $area > 0 ? round($infestadores / $area, 0) : null,
                    // g de madre por infestador
                    'gramos_por_infestador' => $infestadores > 0 ? round($kg * 1000 / $infestadores, 2) : null,
                    'origenes' => $porMetodo->pluck('campo_origen_nombre')->filter()->unique()->implode(', '),
                ];
            }
        }
        return [
            'resumen' => $resumen,
            'detalle' => $filas->map(fn($f) => [
                'fecha' => $f->fecha, 'tipo' => $f->tipo_infestacion, 'metodo' => $f->metodo, 'kg_madres' => (float) $f->kg_madres,
                'infestadores' => (float) $f->infestadores, 'origen' => $f->campo_origen_nombre,
            ])->all(),
        ];
    }

    /**
     * Cochinilla PRODUCIDA por la campaña (por sublote si lo tiene), agrupada por tipo.
     *
     * Se cuenta por dueño (campania_origen_id), no por el campo donde se recogió: la cosecha de mamá infesta otros
     * campos y vuelve como ingreso de infestadores en ese otro campo semanas después, y esos kilos son de la campaña
     * cosechada (regla del ingeniero, 19/10/2026). Ingresos sin dueño registrado: los del propio campo.
     * Un retorno de infestadores con mamá de varios campos se reparte (cochinilla_ingreso_origenes): a esta campaña
     * le toca solo su parte, aplicada en proporción a cada sublote.
     */
    private function cosecha(CampoCampania $c, string $corte, float $area): array
    {
        $tipos = DB::table('cochinilla_observaciones')->get()->keyBy('codigo');
        // Parte de cada ingreso repartido que es de esta campaña
        $partePropia = DB::table('cochinilla_ingreso_origenes')->where('campania_origen_id', $c->id)
            ->selectRaw('cochinilla_ingreso_id, SUM(kg) AS kg')->groupBy('cochinilla_ingreso_id')->pluck('kg', 'cochinilla_ingreso_id');
        $repartidos = DB::table('cochinilla_ingreso_origenes')->distinct()->pluck('cochinilla_ingreso_id')->flip();
        $ingresos = DB::table('cochinilla_ingresos')
            ->where(fn($q) => $q->whereIn('id', $partePropia->keys())
                ->orWhere(fn($w) => $w->whereNotIn('id', $repartidos->keys())->where(fn($x) => $x->where('campania_origen_id', $c->id)
                    ->orWhere(fn($y) => $y->whereNull('campania_origen_id')->where('campo_campania_id', $c->id)))))
            ->where('fecha', '<=', $corte)->orderBy('fecha')->get();
        $detalles = DB::table('cochinilla_ingreso_detalles')->whereIn('cochinilla_ingreso_id', $ingresos->pluck('id'))->get()->groupBy('cochinilla_ingreso_id');

        $lineas = [];
        foreach ($ingresos as $i) {
            $factor = isset($partePropia[$i->id]) && (float) $i->total_kilos > 0 ? (float) $partePropia[$i->id] / (float) $i->total_kilos : 1.0;
            $partes = $detalles->get($i->id) ?? collect([(object) ['observacion' => $i->observacion, 'total_kilos' => $i->total_kilos, 'fecha' => $i->fecha]]);
            foreach ($partes as $p) {
                $lineas[] = ['fecha' => $p->fecha ?? $i->fecha, 'lote' => $i->lote, 'codigo' => $p->observacion, 'recogido_en' => $i->campo,
                    'tipo' => $tipos[$p->observacion]->descripcion ?? ($p->observacion ?: 'Sin tipo'), 'kg' => round((float) $p->total_kilos * $factor, 2),
                    'parte' => $factor < 0.9999 ? round($factor * 100, 1) : null];
            }
        }
        $resumen = collect($lineas)->groupBy('codigo')->map(fn($g, $tipo) => [
            'tipo' => $g->first()['tipo'],
            'es_mama' => (bool) ($tipos[$tipo]->es_cosecha_mama ?? false),
            'es_vendible' => (bool) ($tipos[$tipo]->es_vendible ?? false),
            'ingresos' => $g->count(),
            'recogido_en' => $g->pluck('recogido_en')->unique()->sort()->implode(', '),
            'desde' => $g->min('fecha'),
            'hasta' => $g->max('fecha'),
            'kg' => round($g->sum('kg'), 2),
            'kg_ha' => $area > 0 ? round($g->sum('kg') / $area, 2) : null,
        ])->values()->all();

        return ['resumen' => $resumen, 'detalle' => $lineas];
    }

    private function salidas(CampoCampania $c, string $inicio, string $corte, array $categorias): Collection
    {
        return DB::table('almacen_producto_salidas as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->where('s.campo_nombre', $c->campo)
            ->whereBetween('s.fecha_reporte', [$inicio, $corte])
            ->whereIn('p.categoria_codigo', $categorias)
            ->orderBy('s.fecha_reporte')
            ->get(['s.fecha_reporte as fecha', 's.producto_id', 'p.nombre_comercial as producto', 'p.ingrediente_activo',
                'p.categoria_codigo as categoria', 's.cantidad', 's.total_costo']);
    }

    private function fertilizacion(CampoCampania $c, string $inicio, string $corte, float $area, array $rangos): array
    {
        $salidas = $this->salidas($c, $inicio, $corte, ['fertilizante']);
        $porcentajes = DB::table('producto_nutrientes')->whereIn('producto_id', $salidas->pluck('producto_id')->unique())->get()
            ->groupBy('producto_id')->map(fn($g) => $g->mapWithKeys(fn($n) => [mb_strtolower($n->nutriente_codigo) => (float) $n->porcentaje]));
        $ha = fn($v) => $area > 0 ? round($v / $area, 2) : null;

        $productos = [];
        foreach ($salidas->groupBy('producto_id') as $productoId => $g) {
            $kg = (float) $g->sum('cantidad');
            $porEtapa = [1 => 0.0, 2 => 0.0, 3 => 0.0];
            foreach ($g as $s) {
                if ($e = CampaniaInformeMensualReglas::etapaDeFecha($rangos, $s->fecha)) {
                    $porEtapa[$e] += (float) $s->cantidad;
                }
            }
            $fila = [
                'producto' => $g->first()->producto,
                'aplicaciones' => $g->pluck('fecha')->unique()->count(),
                'kg' => round($kg, 2),
                'kg_ha' => $ha($kg),
                'etapas_kg_ha' => array_map($ha, $porEtapa),
                'nutrientes_kg_ha' => [],
            ];
            foreach (CampaniaInformeMensualReglas::NUTRIENTES as $codigo => $_) {
                $pct = (float) ($porcentajes[$productoId][$codigo] ?? 0);
                $fila['nutrientes_kg_ha'][$codigo] = $pct > 0 ? $ha($kg * $pct / 100) : null;
            }
            $pctN = (float) ($porcentajes[$productoId]['n'] ?? 0);
            foreach ($porEtapa as $e => $kgEtapa) {
                $nitrogenoEtapa[$e] = ($nitrogenoEtapa[$e] ?? 0) + $kgEtapa * $pctN / 100;
            }
            $productos[] = $fila;
        }
        $nitrogenoEtapa = array_map(fn($v) => (float) $ha($v), ($nitrogenoEtapa ?? []) + [1 => 0.0, 2 => 0.0, 3 => 0.0]);
        ksort($nitrogenoEtapa);
        usort($productos, fn($a, $b) => $b['kg'] <=> $a['kg']);

        $totales = [];
        foreach (CampaniaInformeMensualReglas::NUTRIENTES as $codigo => $_) {
            $totales[$codigo] = round(array_sum(array_map(fn($p) => (float) $p['nutrientes_kg_ha'][$codigo], $productos)), 2);
        }
        $sinNutrientes = array_values(array_map(fn($p) => $p['producto'],
            array_filter($productos, fn($p) => !array_filter($p['nutrientes_kg_ha']))));

        return [
            'productos' => $productos,
            'nutrientes_total_kg_ha' => $totales,
            'nitrogeno_etapa_kg_ha' => $nitrogenoEtapa,
            'productos_sin_nutrientes' => $sinNutrientes,
            'detalle' => $salidas->map(fn($s) => ['fecha' => $s->fecha, 'producto' => $s->producto, 'kg' => (float) $s->cantidad])->all(),
        ];
    }

    private function sanidad(CampoCampania $c, string $inicio, string $corte, float $area): array
    {
        $salidas = $this->salidas($c, $inicio, $corte, CampaniaInformeMensualReglas::CATEGORIAS_SANIDAD);
        $productos = $salidas->groupBy('producto_id')->map(fn($g) => [
            'categoria' => ucfirst($g->first()->categoria),
            'producto' => $g->first()->producto,
            'ingrediente' => $g->first()->ingrediente_activo,
            'aplicaciones' => $g->pluck('fecha')->unique()->count(),
            'primera' => $g->min('fecha'),
            'ultima' => $g->max('fecha'),
            'cantidad' => round((float) $g->sum('cantidad'), 3),
            'cantidad_ha' => $area > 0 ? round((float) $g->sum('cantidad') / $area, 3) : null,
        ])->sortBy([['categoria', 'asc'], ['primera', 'asc']])->values()->all();

        return [
            'productos' => $productos,
            'detalle' => $salidas->map(fn($s) => [
                'fecha' => $s->fecha, 'categoria' => $s->categoria, 'producto' => $s->producto, 'cantidad' => (float) $s->cantidad,
            ])->all(),
        ];
    }

    private function riego(CampoCampania $c, array $rangos): array
    {
        $descarga = (float) $c->riego_descarga_ha_hora ?: CampaniaInformeMensualReglas::DESCARGA_RIEGO_DEFECTO;
        $horasSql = "SUM((TIME_TO_SEC(hora_fin) - TIME_TO_SEC(hora_inicio) + IF(hora_fin < hora_inicio, 86400, 0)) / 3600)";
        $filas = [];
        foreach (CampaniaInformeMensualReglas::ETAPAS as $n => $nombre) {
            $r = $rangos[$n];
            $fila = ['etapa' => $n, 'nombre' => $nombre, 'dias' => 0, 'registros' => 0, 'horas' => 0.0, 'm3_ha' => 0.0];
            if ($r) {
                $q = DB::table('reg_registro_diario')->where('campo', $c->campo)->where('tipo_labor', 'Riego')
                    ->whereBetween('fecha', [$r['desde'], $r['hasta']])
                    ->selectRaw("COUNT(*) AS registros, COUNT(DISTINCT fecha) AS dias, {$horasSql} AS horas")->first();
                $fila['dias'] = (int) $q->dias;
                $fila['registros'] = (int) $q->registros;
                $fila['horas'] = round((float) $q->horas, 2);
                $fila['m3_ha'] = round((float) $q->horas * $descarga, 2);
            }
            $filas[] = $fila;
        }
        return [
            'descarga_m3_ha_hora' => $descarga,
            'descarga_por_defecto' => !(float) $c->riego_descarga_ha_hora,
            'filas' => $filas,
        ];
    }

    /** Costos de la BDD hasta el corte, con el reparto por etapa de cada partida. */
    private function costos(CampoCampania $c, string $inicio, string $corte, array $rangos): array
    {
        $total = $this->costos->agrupar($c, $inicio, $corte);
        $clave = fn($p) => "{$p['seccion']}|{$p['grupo']}|{$p['item']}";

        $partidas = [];
        foreach ($total['filas'] as $p) {
            $partidas[$clave($p)] = $p + ['etapas_soles' => [1 => 0.0, 2 => 0.0, 3 => 0.0], 'etapas_cantidad' => [1 => 0.0, 2 => 0.0, 3 => 0.0]];
        }
        foreach ($rangos as $n => $r) {
            if (!$r) {
                continue;
            }
            foreach ($this->costos->agrupar($c, $r['desde'], $r['hasta'])['filas'] as $p) {
                if (isset($partidas[$clave($p)])) {
                    $partidas[$clave($p)]['etapas_soles'][$n] = (float) $p['costo_soles'];
                    $partidas[$clave($p)]['etapas_cantidad'][$n] = (float) $p['cantidad'];
                }
            }
        }

        $secciones = [];
        foreach (CampaniaCostoProduccionReglas::SECCIONES as $s => [$numero, $titulo, $bloque, , $tipoCantidad]) {
            $secciones[$s] = ['clave' => $s, 'numero' => $numero, 'titulo' => $titulo, 'bloque' => $bloque, 'unidad' => $tipoCantidad, 'partidas' => []];
        }
        foreach ($partidas as $p) {
            $secciones[$p['seccion']]['partidas'][] = [
                'grupo' => $p['grupo'], 'item' => $p['item'], 'codigo' => $p['codigo'],
                'cantidad' => $p['cantidad'], 'costo_soles' => round((float) $p['costo_soles'], 2),
                'etapas_soles' => array_map(fn($v) => round($v, 2), $p['etapas_soles']),
                'etapas_cantidad' => array_map(fn($v) => round($v, 4), $p['etapas_cantidad']),
            ];
        }
        $secciones = array_values(array_filter($secciones, fn($s) => $s['partidas'] || $s['clave'] !== 'otros'));

        return [
            'secciones' => $secciones,
            'total_soles' => round(array_sum(array_column($total['filas'], 'costo_soles')), 2),
            'filas_origen' => $total['filas_origen'],
            'fecha_desde' => $total['fecha_desde'],
            'fecha_hasta' => $total['fecha_hasta'],
        ];
    }

    /**
     * Costo de cada mes de la campaña en soles y en US$ con el tipo de cambio de ese mes (promedio de Caja en el mes;
     * si el mes no tiene, el último anterior; si no hay ninguno, el del corte).
     *
     * @return array<int, array{periodo: string, soles: float, tipo_cambio: float, usd: ?float}>
     */
    private function costoPorMes(CampoCampania $c, string $inicio, string $corte, float $tcCorte): array
    {
        $soles = DB::table('resumen_costo_diarios')->where('campania', $c->nombre_campania)->where('campo', $c->campo)
            ->whereBetween('fecha', [$inicio, $corte])
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') AS periodo, SUM(costo_total) AS soles")->groupBy('periodo')->pluck('soles', 'periodo');
        $filas = [];
        foreach (CampaniaInformeMensualReglas::mesesDeCampania($c, Carbon::parse($corte)) as $m) {
            $tc = $this->tipoCambioMensual($m['anio'], $m['mes'], $corte) ?: $tcCorte;
            $monto = round((float) ($soles[$m['periodo']] ?? 0), 2);
            $filas[] = ['periodo' => $m['periodo'], 'soles' => $monto, 'tipo_cambio' => $tc, 'usd' => $tc > 0 ? round($monto / $tc, 2) : null];
        }
        return $filas;
    }

    private function tipoCambioMensual(int $anio, int $mes, string $corte): float
    {
        $desde = CampaniaInformeMensualReglas::inicioMes($anio, $mes)->toDateString();
        $hasta = min(CampaniaInformeMensualReglas::finMes($anio, $mes)->toDateString(), $corte);
        $promedio = CajaTipoCambio::whereBetween('fecha', [$desde, $hasta])->avg('valor');
        return round((float) ($promedio ?: CajaTipoCambio::where('fecha', '<=', $hasta)->orderByDesc('fecha')->value('valor')), 4);
    }

    private function ventas(CampoCampania $c, string $corte): array
    {
        return DB::table('venta_facturada_cochinillas')->where('campo_campania_id', $c->id)->where('fecha', '<=', $corte)->orderBy('fecha')->get()
            ->map(fn($v) => [
                'fecha' => $v->fecha, 'factura' => $v->factura, 'tipo' => $v->tipo_venta, 'comprador' => $v->comprador,
                'kg' => (float) $v->kg, 'precio_usd' => (float) $v->precio_venta_dolares,
                'total_usd' => round((float) $v->kg * (float) $v->precio_venta_dolares, 2),
                'acido_carminico' => $v->punto_acido_carminico !== null ? (float) $v->punto_acido_carminico : null,
            ])->all();
    }

    private function rentabilidad(array $datos, float $area, float $tc): array
    {
        $costoSoles = $datos['costos']['total_soles'];
        $costoUsd = $tc > 0 ? $costoSoles / $tc : null;
        $kgVendidos = array_sum(array_column($datos['ventas'], 'kg'));
        $ingresoUsd = array_sum(array_column($datos['ventas'], 'total_usd'));
        // Producción: kilos de cochinilla vendible ingresados (sin la mamá que vuelve a infestar)
        $kgProduccion = array_sum(array_map(fn($r) => $r['es_vendible'] ? $r['kg'] : 0, $datos['cosecha']['resumen']));

        $fila = fn(?float $costo, float $kg, float $ingreso) => [
            'costo_usd' => $costo !== null ? round($costo, 2) : null,
            'produccion_kg' => round($kg, 2),
            'costo_kg_usd' => $costo !== null && $kg > 0 ? round($costo / $kg, 2) : null,
            'precio_kg_usd' => $kgVendidos > 0 ? round($ingresoUsd / $kgVendidos, 2) : null,
            'ingreso_usd' => round($ingreso, 2),
            'utilidad_usd' => $costo !== null && $ingreso > 0 ? round($ingreso - $costo, 2) : null,
            'rentabilidad_pct' => $costo && $ingreso > 0 ? round(($ingreso - $costo) / $costo * 100, 2) : null,
        ];
        return [
            'total' => $fila($costoUsd, $kgProduccion, $ingresoUsd),
            'por_ha' => $area > 0 ? $fila($costoUsd !== null ? $costoUsd / $area : null, $kgProduccion / $area, $ingresoUsd / $area) : null,
        ];
    }

    /**
     * Índices productivos (bloque "Índices productivos de evaluación" del Excel), por etapa:
     * - pencas: brotes aptos 2° + 3° piso por ha de la última evaluación de brotes dentro de la etapa;
     * - nitrógeno: kg/ha de N aportado en la etapa (según el % de N de cada fertilizante);
     * - agua: m³/ha de riego; hormonas: giberelina y Dropp (productos de sanidad cuyo nombre los contiene);
     * - cochinilla: kg/ha vendibles cosechados, kg de N por kg y gramos por infestador.
     */
    private function indices(CampoCampania $c, array $datos, array $rangos, float $area): array
    {
        $porEtapa = fn(callable $fn) => array_combine([1, 2, 3], array_map(fn($e) => $rangos[$e] ? $fn($rangos[$e], $e) : null, [1, 2, 3]));

        $brotes = EvalBrotesPorPiso::with('detalles')->where('campania_id', $c->id)->orderBy('fecha')->get();
        $pencas = $porEtapa(function ($r) use ($brotes) {
            $ultima = $brotes->filter(fn($b) => ($f = Carbon::parse($b->fecha)->toDateString()) >= $r['desde'] && $f <= $r['hasta'])->last();
            return $ultima ? round((float) $ultima->promedio_actual_total_brotes_2y3piso, 2) : null;
        });
        $nitrogeno = $datos['fertilizacion']['nitrogeno_etapa_kg_ha'];
        $gramosNPenca = [];
        foreach ([1, 2, 3] as $e) {
            $gramosNPenca[$e] = ($pencas[$e] ?? 0) > 0 ? round($nitrogeno[$e] * 1000 / $pencas[$e], 4) : null;
        }
        $agua = [];
        foreach ($datos['riego']['filas'] as $f) {
            $agua[$f['etapa']] = $f['m3_ha'];
        }

        $hormona = function (string $patron) use ($datos, $rangos, $area) {
            $porEt = [1 => 0.0, 2 => 0.0, 3 => 0.0];
            foreach ($datos['sanidad']['detalle'] as $s) {
                if (preg_match($patron, mb_strtoupper($s['producto'])) && ($e = CampaniaInformeMensualReglas::etapaDeFecha($rangos, $s['fecha']))) {
                    $porEt[$e] += (float) $s['cantidad'];
                }
            }
            return array_map(fn($v) => $area > 0 ? round($v / $area, 3) : null, $porEt);
        };

        $kgVendible = array_sum(array_map(fn($r) => $r['es_vendible'] ? (float) $r['kg_ha'] : 0, $datos['cosecha']['resumen']));
        $kgVendibleLote = array_sum(array_map(fn($r) => $r['es_vendible'] ? (float) $r['kg'] : 0, $datos['cosecha']['resumen']));
        $nTotal = array_sum($nitrogeno);
        $infestadores = [];
        foreach ($datos['infestaciones']['resumen'] as $i) {
            $infestadores[] = [
                'nombre' => "{$i['tipo']} – {$i['metodo']}",
                'gramos_madre' => $i['gramos_por_infestador'],
                'gramos_produccion' => $i['infestadores'] > 0 && $i['tipo'] === 'Infestación' ? round($kgVendibleLote * 1000 / $i['infestadores'], 2) : null,
            ];
        }

        return [
            'pencas_ha' => $pencas,
            'nitrogeno_kg_ha' => $nitrogeno,
            'gramos_n_por_penca' => $gramosNPenca,
            'agua_m3_ha' => $agua + [1 => 0, 2 => 0, 3 => 0],
            'giberelina_ha' => $hormona('/GIBER/'),
            'dropp_ha' => $hormona('/DROPP/'),
            'cochinilla_kg_ha' => round($kgVendible, 2),
            'kg_n_por_kg_cochinilla' => $kgVendible > 0 ? round($nTotal / $kgVendible, 3) : null,
            'infestadores' => $infestadores,
        ];
    }

    // ------------------------------------------------------------------------------------------------ avisos

    /**
     * Lo que falta para que el informe sea confiable: datos de la campaña, meses sin costos en la BDD, meses sin
     * riego, planilla sin costo y el informe del mes anterior.
     *
     * @return array<int, array{nivel: string, tipo: string, mensaje: string}>
     */
    private function advertencias(CampoCampania $c, array $datos, int $anio, int $mes, string $inicio, string $corte): array
    {
        $avisos = [];
        $aviso = function (string $nivel, string $tipo, string $mensaje) use (&$avisos) {
            $avisos[] = compact('nivel', 'tipo', 'mensaje');
        };

        if ((float) $c->area <= 0) {
            $aviso('error', 'campania', 'La campaña no tiene área: no se calculan los valores por hectárea.');
        }
        if ($datos['ficha']['tipo_cambio'] <= 1) {
            $aviso('error', 'campania', 'No hay tipo de cambio para el corte: los montos en US$ no son válidos. Regístralo en Caja → Tipo de cambio o en la campaña.');
        } elseif ($datos['ficha']['tipo_cambio_fuente'] === 'campania') {
            $aviso('aviso', 'campania', 'Tipo de cambio tomado de la campaña (no hay uno de caja a la fecha de corte).');
        }
        $tcFecha = $datos['ficha']['tipo_cambio_fecha'] ?? null;
        if ($tcFecha && Carbon::parse($tcFecha)->diffInDays(Carbon::parse($corte)) > 7) {
            $aviso('aviso', 'campania', 'El último tipo de cambio de caja es del ' . Carbon::parse($tcFecha)->format('d/m/Y') . ': registra el del cierre de mes.');
        }
        if ($datos['riego']['descarga_por_defecto']) {
            $aviso('aviso', 'campania', 'La campaña no tiene descarga de riego (m³/ha/hora): se usó ' . CampaniaInformeMensualReglas::DESCARGA_RIEGO_DEFECTO . '.');
        }
        if ($datos['fertilizacion']['productos_sin_nutrientes']) {
            $aviso('aviso', 'fertilizacion', 'Fertilizantes sin % de nutrientes registrado (no suman en N, P, K…): '
                . implode(', ', $datos['fertilizacion']['productos_sin_nutrientes']) . '.');
        }

        // Meses de la campaña hasta el corte
        $periodos = array_column(CampaniaInformeMensualReglas::mesesDeCampania($c, Carbon::parse($corte)), 'periodo');
        $mesSql = "DATE_FORMAT(fecha, '%Y-%m')";

        $global = DB::table('resumen_costo_diarios')->whereBetween('fecha', [$inicio, $corte])
            ->selectRaw("{$mesSql} AS periodo, origen_tipo, COUNT(*) AS filas, SUM(costo_total) AS costo")
            ->groupBy('periodo', 'origen_tipo')->get();
        $propio = DB::table('resumen_costo_diarios')->whereBetween('fecha', [$inicio, $corte])
            ->where('campania', $c->nombre_campania)->where('campo', $c->campo)
            ->selectRaw("{$mesSql} AS periodo, origen_tipo, COUNT(*) AS filas, SUM(costo_total) AS costo")
            ->groupBy('periodo', 'origen_tipo')->get();

        foreach (CampaniaInformeMensualReglas::COBERTURA_COSTOS as $clave => [$nombre, $origenes]) {
            $sinRegistrar = array_values(array_filter($periodos, fn($p) => !$global->where('periodo', $p)->whereIn('origen_tipo', $origenes)->sum('filas')));
            if ($sinRegistrar) {
                $aviso('error', 'cobertura', "{$nombre}: la BDD de costos no tiene registros en " . CampaniaInformeMensualReglas::rangosDeMeses($sinRegistrar)
                    . '. Hay que registrarlos/consolidarlos para que el acumulado sea válido.');
            }
            if ($clave === 'mano_obra') {
                $sinCampania = array_values(array_filter($periodos, fn($p) => !in_array($p, $sinRegistrar, true)
                    && !$propio->where('periodo', $p)->whereIn('origen_tipo', $origenes)->sum('filas')));
                if ($sinCampania) {
                    $aviso('aviso', 'cobertura', 'Sin mano de obra de esta campaña en ' . CampaniaInformeMensualReglas::rangosDeMeses($sinCampania)
                        . ' (la BDD sí tiene otros campos esos meses).');
                }
            }
        }
        $planillaCero = $propio->where('origen_tipo', 'planilla')->filter(fn($r) => $r->filas > 0 && abs((float) $r->costo) < 0.01)->pluck('periodo')->sort()->values()->all();
        if ($planillaCero) {
            $aviso('error', 'cobertura', 'Planilla con costo 0 en ' . CampaniaInformeMensualReglas::rangosDeMeses($planillaCero)
                . ': hay horas pero falta calcular/cerrar la planilla de esos meses.');
        }

        $riegoMeses = DB::table('reg_registro_diario')->where('campo', $c->campo)->where('tipo_labor', 'Riego')
            ->whereBetween('fecha', [$inicio, $corte])->selectRaw("DISTINCT {$mesSql} AS periodo")->pluck('periodo')->all();
        $sinRiego = array_values(array_diff($periodos, $riegoMeses));
        if ($sinRiego) {
            $aviso('aviso', 'cobertura', 'Sin registros de riego en ' . CampaniaInformeMensualReglas::rangosDeMeses($sinRiego) . '.');
        }

        // Informe del mes anterior (si la campaña ya existía ese mes)
        $anterior = CampaniaInformeMensualReglas::inicioMes($anio, $mes)->subMonthNoOverflow();
        if ($anterior->gte(Carbon::parse($inicio)->startOfMonth())
            && !CampaniaInformeMensual::where('campo_campania_id', $c->id)->where('anio', $anterior->year)->where('mes', $anterior->month)->exists()) {
            $aviso('aviso', 'secuencia', "No se ha generado el informe de {$anterior->format('m/Y')} de esta campaña.");
        }

        return $avisos;
    }

    // ------------------------------------------------------------------------------------------------ apoyo

    /** Fecha del evento según la campaña o, si no la tiene, la primera infestación/reinfestación registrada. */
    private function fechaEvento(CampoCampania $c, string $tipo, $fechaCampania, string $corte): ?string
    {
        $fecha = $fechaCampania ? Carbon::parse($fechaCampania)->toDateString()
            : DB::table('cochinilla_infestaciones')->where('campo_campania_id', $c->id)->where('tipo_infestacion', $tipo)->min('fecha');
        return $fecha && $fecha <= $corte ? $fecha : null;
    }

    /** @return array{0: float, 1: string, 2: ?string} tipo de cambio, de dónde salió (caja | campania | ninguno) y su fecha */
    private function tipoCambio(CampoCampania $c, string $corte): array
    {
        $caja = CajaTipoCambio::where('fecha', '<=', $corte)->orderByDesc('fecha')->first(['fecha', 'valor']);
        if ($caja) {
            return [(float) $caja->valor, 'caja', Carbon::parse($caja->fecha)->toDateString()];
        }
        return (float) $c->tipo_cambio > 1 ? [(float) $c->tipo_cambio, 'campania', null] : [0.0, 'ninguno', null];
    }
}
