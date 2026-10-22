<?php

namespace App\Services\Campania\Historia;

use App\Models\CampoCampania;
use App\Services\Cochinilla\Origen\CochinillaOrigenProceso;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Historia de una campaña contada en palabras: cuándo empezó, qué labores se hicieron y con cuántos trabajadores,
 * cómo se infestó, qué se cosechó y a dónde fue, qué se recuperó, riegos, insumos, evaluaciones y ventas.
 * El informe mensual es un diagnóstico por mes; esto es la línea de tiempo de toda la campaña (hasta hoy si sigue abierta).
 *
 * Mano de obra: resumen_costo_diarios (BDD) del campo en el rango efectivo de la campaña. Una misma labor con más de
 * DIAS_CORTE_LABOR días sin hacerse se cuenta como otro momento.
 */
class CampaniaHistoriaConsulta
{
    public const DIAS_CORTE_LABOR = 7;

    /**
     * @return array{
     *   intro: string[],
     *   eventos: array<int, array{fecha: string, dia: int, tipo: string, texto: string, detalle?: string[]}>,
     *   resumen: string[],
     * }
     */
    public function construir(CampoCampania $c): array
    {
        $hoy = Carbon::today()->toDateString();
        $inicio = $c->fecha_inicio->toDateString();
        $fin = min(ConsolidarCostoManoObraServicio::finEfectivoCampania($c) ?? $hoy, $hoy);
        $cerrada = (bool) $c->fecha_fin;

        $eventos = collect()
            ->merge($this->manoObra($c, $inicio, $fin))
            ->merge($this->infestaciones($c))
            ->merge($this->cosechas($c, $inicio, $fin))
            ->merge($this->mamaEntregada($c, $inicio, $fin, $cerrada))
            ->merge($this->recuperaciones($c))
            ->merge($this->riego($c, $inicio, $fin))
            ->merge($this->insumos($c, $inicio, $fin))
            ->merge($this->evaluaciones($c))
            ->merge($this->ventas($c));

        if ($cerrada) {
            $eventos->push($this->evento($c->fecha_fin->toDateString(), 'cierre',
                'Se cerró la campaña (último día de cosecha). Lo que se hizo en el campo después va a la siguiente campaña.'));
        }

        $eventos = $eventos->map(fn($e) => $e + ['dia' => (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($e['fecha']), false) + 1])
            ->sortBy([['fecha', 'asc'], ['orden', 'asc']])->values();

        return [
            'intro' => $this->intro($c, $inicio, $fin, $eventos),
            'eventos' => $eventos->all(),
            'resumen' => $this->resumen($c, $inicio, $fin, $eventos),
            'faltantes' => $this->faltantes($eventos),
        ];
    }

    /**
     * Labores que hacen esperar un registro que no está: se infestó (mano de obra) pero no hay infestación
     * registrada, se recogieron infestadores o se cosechó pero no hay ingreso de cochinilla cerca de esa fecha.
     */
    private function faltantes(Collection $eventos): array
    {
        $reglas = [
            ['/coloca\w* infestador/i', ['infestacion'], 'infestación', 'Cochinilla → Infestación'],
            ['/recojo infestador|vaciado infestador/i', ['recojo', 'cosecha'], 'ingreso de infestadores', 'Cochinilla → Ingreso'],
            ['/cosecha/i', ['cosecha', 'mama'], 'ingreso de cosecha', 'Cochinilla → Ingreso'],
        ];
        $avisos = [];
        foreach ($eventos->where('tipo', 'labor') as $labor) {
            foreach ($reglas as [$patron, $tipos, $que, $donde]) {
                if (!preg_match($patron, $labor['datos']['labor'] ?? '')) {
                    continue;
                }
                $cerca = $eventos->whereIn('tipo', $tipos)->contains(fn($e) => abs(Carbon::parse($e['fecha'])->diffInDays(Carbon::parse($labor['fecha']), false))
                    <= CochinillaOrigenProceso::DIAS_MISMA_INFESTACION);
                if (!$cerca) {
                    $avisos["{$que}|{$labor['fecha']}"] = "Hubo «{$labor['datos']['labor']}» el " . $this->fecha($labor['fecha'])
                        . " pero no hay {$que} registrado cerca de esa fecha ({$donde}).";
                }
            }
        }
        return array_values($avisos);
    }

    private function intro(CampoCampania $c, string $inicio, string $fin, Collection $eventos): array
    {
        $lineas = [];
        $siembra = DB::table('siembras')->where('campo_nombre', $c->campo)->where('fecha_siembra', '<=', $inicio)
            ->orderByDesc('fecha_siembra')->value('fecha_siembra');
        $primeraSiembra = DB::table('siembras')->where('campo_nombre', $c->campo)->min('fecha_siembra');
        $numero = CampoCampania::where('campo', $c->campo)->where('fecha_inicio', '<=', $inicio)
            ->when($siembra, fn($q) => $q->where('fecha_inicio', '>=', $siembra))->count();

        $texto = "La campaña {$c->nombre_campania} del campo {$c->campo}"
            . ($c->area ? ' (' . number_format((float) $c->area, 2) . ' ha)' : '')
            . ' empezó el ' . $this->fecha($inicio);
        if ($siembra) {
            $texto .= ". Es la {$numero}.ª campaña desde que se sembró el " . $this->fecha($siembra);
            if ($primeraSiembra && $primeraSiembra !== $siembra) {
                $texto .= ' (el campo se sembró por primera vez el ' . $this->fecha($primeraSiembra) . ')';
            }
        } else {
            $texto .= ". Es la {$numero}.ª campaña registrada del campo (no hay fecha de siembra registrada)";
        }
        $lineas[] = $texto . '.';

        $dias = (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($fin)) + 1;
        $lineas[] = $c->fecha_fin
            ? "Duró {$dias} días: terminó el " . $this->fecha($c->fecha_fin->toDateString()) . '.'
            : "Sigue abierta: lleva {$dias} días (hasta hoy).";
        if ($c->variedad_tuna || $c->sistema_cultivo) {
            $lineas[] = 'Tuna ' . trim(($c->variedad_tuna ?? '') . ($c->sistema_cultivo ? ", sistema {$c->sistema_cultivo}" : '')) . '.';
        }
        return $lineas;
    }

    /** Cada labor agrupada en "momentos" (días seguidos o con pocos días de diferencia). */
    private function manoObra(CampoCampania $c, string $inicio, string $fin): Collection
    {
        $filas = DB::table('resumen_costo_diarios')->where('campo', $c->campo)->whereBetween('fecha', [$inicio, $fin])
            ->whereIn('origen_tipo', ['planilla', 'cuadrilla'])
            ->whereNotNull('labor')->where('labor', '<>', '')
            ->orderBy('fecha')
            ->get(['fecha', 'labor', 'labor_nombre', 'trabajador', 'origen_tipo', 'minutos', 'costo_total']);

        $momentos = collect();
        foreach ($filas->groupBy('labor') as $porLabor) {
            $actual = null;
            foreach ($porLabor->groupBy(fn($f) => Carbon::parse($f->fecha)->toDateString()) as $dia => $delDia) {
                if ($actual && Carbon::parse($actual['hasta'])->diffInDays(Carbon::parse($dia)) > self::DIAS_CORTE_LABOR) {
                    $momentos->push($actual);
                    $actual = null;
                }
                $actual ??= ['desde' => $dia, 'hasta' => $dia, 'labor' => $delDia->first()->labor_nombre ?: "Labor {$delDia->first()->labor}",
                    'dias' => 0, 'trabajadores' => [], 'planilla' => [], 'cuadrilla' => [], 'minutos' => 0, 'costo' => 0.0];
                $actual['hasta'] = $dia;
                $actual['dias']++;
                foreach ($delDia as $f) {
                    $actual['trabajadores'][$f->trabajador] = true;
                    $actual[$f->origen_tipo][$f->trabajador] = true;
                    $actual['minutos'] += (int) $f->minutos;
                    $actual['costo'] += (float) $f->costo_total;
                }
            }
            if ($actual) {
                $momentos->push($actual);
            }
        }

        $primerDia = $momentos->min('desde');
        return $momentos->map(function ($m) use ($primerDia) {
            $n = count($m['trabajadores']);
            $quienes = $this->trabajadores($n, count($m['planilla']), count($m['cuadrilla']));
            $periodo = $m['dias'] === 1 ? 'el ' . $this->fecha($m['desde'])
                : "durante {$m['dias']} días (" . $this->fecha($m['desde']) . ' al ' . $this->fecha($m['hasta']) . ')';
            $horas = round($m['minutos'] / 60, 1);
            $accion = " «{$m['labor']}» {$periodo}: " . number_format($horas, 1) . ' h, S/ ' . number_format($m['costo'], 2) . '.';
            $texto = $m['desde'] === $primerDia
                ? "Se empezó con {$quienes}, que " . ($n === 1 ? 'hizo' : 'hicieron') . $accion
                : ucfirst($quienes) . ' ' . ($n === 1 ? 'hizo' : 'hicieron') . $accion;
            return $this->evento($m['desde'], 'labor', $texto, [], ['horas' => $horas, 'costo' => $m['costo'], 'trabajadores' => $n, 'labor' => $m['labor']]);
        });
    }

    private function trabajadores(int $total, int $planilla, int $cuadrilla): string
    {
        $base = $total === 1 ? '1 trabajador' : "{$total} trabajadores";
        if ($planilla && $cuadrilla) {
            return "{$base} ({$planilla} de planilla y {$cuadrilla} de cuadrilla)";
        }
        return $base . ($cuadrilla ? ' de cuadrilla' : ' de planilla');
    }

    /** Infestaciones del campo, por tanda (días seguidos), con la mamá de cada campo de origen. */
    private function infestaciones(CampoCampania $c): Collection
    {
        // infestadores no es columna: envases × capacidad (igual que CochinillaInfestacion::getInfestadoresAttribute)
        $filas = DB::table('cochinilla_infestaciones')->where('campo_campania_id', $c->id)
            ->selectRaw('cochinilla_infestaciones.*, numero_envases * capacidad_envase AS infestadores')->orderBy('fecha')->get();
        $eventos = collect();
        $tanda = [];
        $cerrarTanda = function () use (&$tanda, $eventos) {
            if (!$tanda) {
                return;
            }
            $g = collect($tanda);
            $desde = Carbon::parse($g->first()->fecha)->toDateString();
            $hasta = Carbon::parse($g->last()->fecha)->toDateString();
            $tipo = $g->first()->tipo_infestacion === 'reinfestacion' ? 'reinfestó' : 'infestó';
            $origenes = $g->groupBy(fn($i) => $i->campo_origen_nombre ?: '¿?')
                ->map(fn($o, $campo) => "campo {$campo} (" . number_format($o->sum('kg_madres'), 2) . ' kg)')->implode(', ');
            $metodos = $g->pluck('metodo')->filter()->unique()->implode(', ');
            $kg = (float) $g->sum('kg_madres');
            $esperado = $kg / CochinillaRecuperacionReglas::factorSeco();
            $eventos->push($this->evento($desde, 'infestacion',
                "Se {$tipo} el campo " . ($desde === $hasta ? 'el ' . $this->fecha($desde) : 'del ' . $this->fecha($desde) . ' al ' . $this->fecha($hasta))
                . ' con ' . number_format($kg, 2) . " kg de mamá fresca de {$origenes}"
                . ($metodos ? " en {$metodos}" : '') . ', ' . number_format((int) $g->sum('infestadores')) . ' infestadores'
                . ' (se esperaría recuperar unos ' . number_format($esperado, 0) . ' kg secos).'));
            $tanda = [];
        };
        foreach ($filas as $f) {
            if ($tanda && (Carbon::parse(end($tanda)->fecha)->diffInDays(Carbon::parse($f->fecha)) > CochinillaOrigenProceso::DIAS_MISMA_INFESTACION
                || end($tanda)->tipo_infestacion !== $f->tipo_infestacion)) {
                $cerrarTanda();
            }
            $tanda[] = $f;
        }
        $cerrarTanda();
        return $eventos;
    }

    /** Cosecha recogida en el campo (sin los retornos de infestadores, que se cuentan aparte). */
    private function cosechas(CampoCampania $c, string $inicio, string $fin): Collection
    {
        $tipos = DB::table('cochinilla_observaciones')->pluck('descripcion', 'codigo');
        $ingresos = DB::table('cochinilla_ingresos')->where('campo', $c->campo)
            ->where(fn($q) => $q->where('campo_campania_id', $c->id)->orWhere(fn($q) => $q->whereNull('campo_campania_id')->whereBetween('fecha', [$inicio, $fin])))
            ->orderBy('fecha')->get(['id', 'lote', 'fecha', 'observacion', 'total_kilos', 'campo_origen', 'campania_origen_id']);

        $eventos = collect();
        $infestadores = $ingresos->filter(fn($i) => str_starts_with((string) $i->observacion, 'infestador'));
        $cosecha = $ingresos->reject(fn($i) => str_starts_with((string) $i->observacion, 'infestador'));

        foreach ($this->rachas($cosecha, 'fecha', CochinillaOrigenProceso::DIAS_MISMA_INFESTACION) as $racha) {
            $desde = $racha->first()->fecha;
            $hasta = $racha->last()->fecha;
            $dias = $racha->pluck('fecha')->unique()->count();
            $porTipo = $racha->groupBy('observacion')
                ->map(fn($g, $tipo) => number_format($g->sum('total_kilos'), 2) . ' kg de ' . mb_strtolower($tipos[$tipo] ?? ($tipo ?: 'sin tipo')))
                ->implode(', ');
            $eventos->push($this->evento($desde, 'cosecha',
                'Se cosechó ' . ($dias === 1 ? 'el ' . $this->fecha($desde) : "durante {$dias} días (" . $this->fecha($desde) . ' al ' . $this->fecha($hasta) . ')')
                . ": {$porTipo}. Lotes " . $racha->pluck('lote')->implode(', ') . '.'));
        }

        foreach ($this->rachas($infestadores, 'fecha', CochinillaOrigenProceso::DIAS_MISMA_INFESTACION) as $racha) {
            $desde = $racha->first()->fecha;
            $origenes = DB::table('cochinilla_ingreso_origenes')->whereIn('cochinilla_ingreso_id', $racha->pluck('id'))
                ->selectRaw('campo_origen, SUM(kg) kg')->groupBy('campo_origen')->pluck('kg', 'campo_origen');
            $de = $origenes->isNotEmpty()
                ? $origenes->map(fn($kg, $campo) => "{$campo} " . number_format($kg, 2) . ' kg')->implode(', ')
                : $racha->groupBy('campo_origen')->map(fn($g, $campo) => ($campo ?: '¿?') . ' ' . number_format($g->sum('total_kilos'), 2) . ' kg')->implode(', ');
            $eventos->push($this->evento($desde, 'recojo',
                'Se recogieron los infestadores: ' . number_format($racha->sum('total_kilos'), 2) . ' kg (lotes ' . $racha->pluck('lote')->implode(', ')
                . "). Los kilos son de los campos de donde vino la mamá: {$de}. La mano de obra es de este campo."));
        }
        return $eventos;
    }

    private function rachas(Collection $filas, string $campo, int $dias): array
    {
        $rachas = [];
        $actual = collect();
        foreach ($filas->sortBy($campo) as $f) {
            if ($actual->isNotEmpty() && Carbon::parse($actual->last()->$campo)->diffInDays(Carbon::parse($f->$campo)) > $dias) {
                $rachas[] = $actual;
                $actual = collect();
            }
            $actual->push($f);
        }
        if ($actual->isNotEmpty()) {
            $rachas[] = $actual;
        }
        return $rachas;
    }

    /** Mamá cosechada en este campo que se usó para infestar otros campos. */
    private function mamaEntregada(CampoCampania $c, string $inicio, string $fin, bool $cerrada): Collection
    {
        $hasta = $cerrada ? Carbon::parse($fin)->addDays(CochinillaOrigenProceso::DIAS_MISMA_INFESTACION)->toDateString() : $fin;
        $filas = DB::table('cochinilla_infestaciones')->where('campo_origen_nombre', $c->campo)
            ->whereBetween('fecha', [$inicio, $hasta])->orderBy('fecha')->get();

        return collect($this->rachas($filas, 'fecha', CochinillaOrigenProceso::DIAS_MISMA_INFESTACION))->map(function ($racha) {
            $desde = Carbon::parse($racha->first()->fecha)->toDateString();
            $destinos = $racha->groupBy('campo_nombre')->map(fn($g, $campo) => "{$campo} (" . number_format($g->sum('kg_madres'), 2) . ' kg'
                . ($g->first()->tipo_infestacion === 'reinfestacion' ? ', reinfestación' : '') . ')')->implode(', ');
            return $this->evento($desde, 'mama',
                'Se cosechó mamá de este campo para infestar: ' . number_format($racha->sum('kg_madres'), 2) . " kg frescos a los campos {$destinos}.");
        })->values();
    }

    /** Lo que volvió de la mamá de este campo puesta en otros campos (repartos de infestadores). */
    private function recuperaciones(CampoCampania $c): Collection
    {
        $filas = DB::table('cochinilla_ingreso_origenes as o')->join('cochinilla_ingresos as i', 'i.id', '=', 'o.cochinilla_ingreso_id')
            ->where('o.campania_origen_id', $c->id)->where('i.campo', '<>', $c->campo)
            ->orderBy('i.fecha')->get(['i.campo', 'i.fecha', 'i.lote', 'o.kg']);
        $factor = CochinillaRecuperacionReglas::factorSeco();

        return $filas->groupBy('campo')->map(function ($g, $campo) use ($c, $factor) {
            $desde = Carbon::parse($g->min('fecha'));
            $puesto = (float) DB::table('cochinilla_infestaciones')->where('campo_nombre', $campo)->where('campo_origen_nombre', $c->campo)
                ->whereBetween('fecha', [$desde->copy()->subDays(CochinillaOrigenProceso::DIAS_INFESTADORES)->toDateString(), $desde->toDateString()])
                ->sum('kg_madres');
            $kg = (float) $g->sum('kg');
            $texto = "Se recuperó de la mamá puesta en el campo {$campo}: " . number_format($kg, 2) . ' kg (lotes ' . $g->pluck('lote')->unique()->implode(', ') . ')';
            if ($puesto > 0) {
                $esperado = $puesto / $factor;
                $texto .= ', de ' . number_format($puesto, 2) . ' kg frescos puestos (~' . number_format($esperado, 0) . ' kg secos esperados, '
                    . number_format($kg / $esperado * 100, 0) . ' %)';
            }
            return $this->evento($desde->toDateString(), 'recuperacion', $texto . '.');
        })->values();
    }

    /** Riego por mes: días regados y horas. */
    private function riego(CampoCampania $c, string $inicio, string $fin): Collection
    {
        $horasSql = '(TIME_TO_SEC(hora_fin) - TIME_TO_SEC(hora_inicio) + IF(hora_fin < hora_inicio, 86400, 0)) / 3600';
        $dias = DB::table('reg_registro_diario')->where('campo', $c->campo)->where('tipo_labor', 'Riego')
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw("fecha, SUM({$horasSql}) AS horas")->groupBy('fecha')->orderBy('fecha')->get();

        return $dias->groupBy(fn($d) => substr((string) $d->fecha, 0, 7))->map(function ($g, $mes) {
            $fecha = Carbon::parse($g->first()->fecha);
            $lista = $g->map(fn($d) => Carbon::parse($d->fecha)->day)->implode(', ');
            return $this->evento($fecha->toDateString(), 'riego',
                'En ' . $fecha->translatedFormat('F Y') . ' se regó ' . $g->count() . ($g->count() === 1 ? ' día' : ' días')
                . " (los días {$lista}) con " . number_format((float) $g->sum('horas'), 1) . ' horas de riego.', [], ['horas' => (float) $g->sum('horas')]);
        })->values();
    }

    /** Insumos aplicados (fertilizantes, pesticidas…) agrupados por día. */
    private function insumos(CampoCampania $c, string $inicio, string $fin): Collection
    {
        $salidas = DB::table('almacen_producto_salidas as s')->join('productos as p', 'p.id', '=', 's.producto_id')
            ->where('s.campo_nombre', $c->campo)->whereBetween('s.fecha_reporte', [$inicio, $fin])
            ->where('p.categoria_codigo', '<>', 'combustible')
            ->orderBy('s.fecha_reporte')
            ->get(['s.fecha_reporte as fecha', 'p.nombre_comercial as producto', 'p.categoria_codigo as categoria', 's.cantidad', 's.total_costo']);

        // Un evento por mes: cada categoría con sus días de aplicación y el total de cada producto
        return $salidas->groupBy(fn($s) => substr((string) $s->fecha, 0, 7))->map(function ($g) {
            $fecha = Carbon::parse($g->first()->fecha);
            $porCategoria = $g->groupBy('categoria')->map(function ($p, $cat) {
                $dias = $p->map(fn($x) => Carbon::parse($x->fecha)->day)->unique()->values();
                $productos = $p->groupBy('producto')
                    ->map(fn($x, $prod) => $prod . ' ' . rtrim(rtrim(number_format((float) $x->sum('cantidad'), 2), '0'), '.'))->implode(', ');
                return "{$cat} " . $dias->count() . ($dias->count() === 1 ? ' vez' : ' veces') . ' (días ' . $dias->implode(', ') . "): {$productos}";
            })->implode('. ');
            return $this->evento($fecha->toDateString(), 'insumo',
                'Insumos de ' . $fecha->translatedFormat('F Y') . ": {$porCategoria}.", [], ['costo' => (float) $g->sum('total_costo')]);
        })->values();
    }

    private function evaluaciones(CampoCampania $c): Collection
    {
        $eventos = collect();
        foreach (DB::table('eval_brotes_por_pisos')->where('campania_id', $c->id)->orderBy('fecha')->get() as $e) {
            $eventos->push($this->evento(Carbon::parse($e->fecha)->toDateString(), 'evaluacion',
                'Se evaluaron los brotes por piso' . ($e->evaluador ? " ({$e->evaluador})" : '') . '.'));
        }
        foreach (DB::table('eval_poblacion_plantas')->where('campania_id', $c->id)->get() as $e) {
            foreach (['fecha_eval_cero' => 'día cero', 'fecha_eval_resiembra' => 'resiembra'] as $col => $nombre) {
                if ($e->$col) {
                    $eventos->push($this->evento(Carbon::parse($e->$col)->toDateString(), 'evaluacion',
                        "Se evaluó la población de plantas ({$nombre})" . ($e->evaluador ? ", evaluador {$e->evaluador}" : '') . '.'));
                }
            }
        }
        foreach (['eval_infest_fecha_primera' => 'primera', 'eval_infest_fecha_segunda' => 'segunda', 'eval_infest_fecha_tercera' => 'tercera'] as $col => $nombre) {
            if ($c->$col) {
                $eventos->push($this->evento(Carbon::parse($c->$col)->toDateString(), 'evaluacion', "Se hizo la {$nombre} evaluación de infestación de pencas."));
            }
        }
        return $eventos;
    }

    private function ventas(CampoCampania $c): Collection
    {
        return DB::table('venta_facturada_cochinillas')->where('campo_campania_id', $c->id)->orderBy('fecha')->get()
            ->map(fn($v) => $this->evento(Carbon::parse($v->fecha)->toDateString(), 'venta',
                'Se vendieron ' . number_format((float) $v->kg, 2) . ' kg' . ($v->tipo_venta ? " ({$v->tipo_venta})" : '')
                . ($v->comprador ? " a {$v->comprador}" : '') . ($v->precio_venta_dolares ? ' a US$ ' . number_format((float) $v->precio_venta_dolares, 2) . '/kg' : '')
                . ($v->factura ? ", factura {$v->factura}" : '') . '.'));
    }

    private function resumen(CampoCampania $c, string $inicio, string $fin, Collection $eventos): array
    {
        $labores = $eventos->where('tipo', 'labor');
        $lineas = [];
        $trabajadores = DB::table('resumen_costo_diarios')->where('campo', $c->campo)->whereBetween('fecha', [$inicio, $fin])
            ->whereIn('origen_tipo', ['planilla', 'cuadrilla'])->distinct()->count('trabajador');
        if ($labores->isNotEmpty()) {
            $lineas[] = 'Mano de obra: ' . number_format($trabajadores) . ' personas distintas trabajaron en ' . $labores->count() . ' momentos de labor, '
                . number_format($labores->sum(fn($e) => $e['datos']['horas']), 0) . ' horas y S/ ' . number_format($labores->sum(fn($e) => $e['datos']['costo']), 2) . '.';
        }
        $riego = $eventos->where('tipo', 'riego');
        if ($riego->isNotEmpty()) {
            $lineas[] = 'Riego: ' . number_format($riego->sum(fn($e) => $e['datos']['horas']), 1) . ' horas en total.';
        }
        $insumos = $eventos->where('tipo', 'insumo');
        if ($insumos->isNotEmpty()) {
            $lineas[] = 'Insumos: ' . $insumos->count() . ' días con aplicaciones, S/ ' . number_format($insumos->sum(fn($e) => $e['datos']['costo']), 2) . '.';
        }
        $vendidos = (float) DB::table('venta_facturada_cochinillas')->where('campo_campania_id', $c->id)->sum('kg');
        if ($vendidos > 0) {
            $lineas[] = 'Ventas: ' . number_format($vendidos, 2) . ' kg facturados.';
        }
        return $lineas;
    }

    private const ORDEN = ['infestacion' => 1, 'labor' => 2, 'riego' => 3, 'insumo' => 4, 'cosecha' => 5, 'mama' => 6, 'recojo' => 7,
        'recuperacion' => 8, 'evaluacion' => 9, 'venta' => 10, 'cierre' => 11];

    private function evento(string $fecha, string $tipo, string $texto, array $detalle = [], array $datos = []): array
    {
        return ['fecha' => Carbon::parse($fecha)->toDateString(), 'tipo' => $tipo, 'orden' => self::ORDEN[$tipo] ?? 99,
            'texto' => $texto, 'detalle' => $detalle, 'datos' => $datos];
    }

    private function fecha(string $fecha): string
    {
        return Carbon::parse($fecha)->format('d/m/Y');
    }
}
