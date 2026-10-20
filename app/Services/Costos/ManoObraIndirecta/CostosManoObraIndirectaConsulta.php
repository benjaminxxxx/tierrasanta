<?php

namespace App\Services\Costos\ManoObraIndirecta;

use App\Models\AlmacenProductoSalida;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Models\InsKardex;
use App\Services\Almacen\Kardex\KardexActualizacionServicio;
use App\Services\Planilla\PlanillaMensualDetalleServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo explicado de la mano de obra indirecta de un mes (todo lo gastado en FDM), en blanco y negro, por
 * sección → grupo → filas. Solo lee: la BDD de mano de obra y los kardex se ponen al día en
 * CostosManoObraIndirectaProceso antes de calcular para guardar.
 *
 * Avisos "no concluyente": mes sin terminar, planilla del mes sin generar, trabajadores sin PLAME, kardex
 * desactualizados, salidas sin costo y combustible de máquinas con distribución pendiente.
 */
class CostosManoObraIndirectaConsulta
{
    private array $avisos = [];

    public function __construct(private KardexActualizacionServicio $kardex)
    {
    }

    /**
     * @return array{anio:int, mes:int, inicio:string, fin:string, secciones:array, avisos:array, concluyente:bool,
     *   blanco:float, negro:float, total:float}
     */
    public function calcular(int $anio, int $mes): array
    {
        $this->avisos = [];
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
        if ($fin >= Carbon::today()->toDateString()) {
            $this->aviso(true, 'El mes aún no termina: faltan días por registrar.');
        }

        $secciones = [
            'planilla' => $this->planilla($anio, $mes, $inicio, $fin),
            'cuadrilla' => $this->cuadrilla($inicio, $fin),
            'gastos_grupo' => $this->gastosGrupo($anio, $mes),
            'combustible' => $this->combustible($inicio, $fin),
            'insumos' => $this->insumos($inicio, $fin),
            'servicios' => $this->servicios($inicio, $fin),
            'otros' => $this->otros($anio, $mes),
        ];
        foreach ($secciones as $clave => &$s) {
            [$titulo, $explicacion] = CostosManoObraIndirectaReglas::SECCIONES[$clave];
            $s = ['clave' => $clave, 'titulo' => $titulo, 'explicacion' => $explicacion] + $this->totalizar($s);
        }
        unset($s);

        $blanco = round(array_sum(array_column($secciones, 'blanco')), 2);
        $negro = round(array_sum(array_column($secciones, 'negro')), 2);
        return [
            'anio' => $anio, 'mes' => $mes, 'inicio' => $inicio, 'fin' => $fin,
            'secciones' => $secciones,
            'avisos' => array_values(array_unique($this->avisos, SORT_REGULAR)),
            'concluyente' => !collect($this->avisos)->contains('no_concluyente', true),
            'blanco' => $blanco, 'negro' => $negro, 'total' => round($blanco + $negro, 2),
        ];
    }

    // ------------------------------------------------------------------ planilla

    private function planilla(int $anio, int $mes, string $inicio, string $fin): array
    {
        $pagos = PlanillaMensualDetalleServicio::obtenerRegistrosMensualesPorCampo($mes, $anio);
        if (!$pagos) {
            $this->aviso(true, 'La planilla del mes no está generada (no hay PLAME con lo pagado): la planilla en FDM sale en 0.');
        }
        $filas = DB::table('resumen_costo_diarios')->whereBetween('fecha', [$inicio, $fin])
            ->whereRaw('UPPER(campo) = ?', [CostosManoObraIndirectaReglas::CAMPO_FDM])
            ->whereIn('origen_tipo', ['planilla', 'planilla_bono_productividad', 'mano_obra_indirecta'])
            ->orderBy('fecha')->get(['fecha', 'origen_tipo', 'labor', 'labor_nombre', 'trabajador', 'plan_empleado_id', 'minutos', 'costo_total']);

        $sinCosto = $filas->where('origen_tipo', 'planilla')->filter(fn($f) => (float) $f->costo_total == 0 && $f->minutos > 0);
        if ($pagos && $sinCosto->isNotEmpty()) {
            $this->aviso(true, $sinCosto->pluck('trabajador')->unique()->count() . ' trabajador(es) de planilla con horas en FDM sin costo (sin horas en la planilla del mes): '
                . $sinCosto->pluck('trabajador')->unique()->take(8)->implode(', ') . '.');
        }

        $sinPlame = [];
        $grupos = [];
        foreach ($filas as $f) {
            $costo = (float) $f->costo_total;
            $pago = $pagos[$f->plan_empleado_id] ?? null;
            $totalPago = $pago ? $pago['sueldo_blanco_pagado'] + $pago['sueldo_negro_pagado'] : 0;
            $esNegro = $f->origen_tipo === 'planilla_bono_productividad' || in_array($f->labor_nombre, CostosManoObraIndirectaReglas::CONCEPTOS_NEGRO, true);
            if ($esNegro) {
                $proporcion = 0.0;
                $nota = 'Bono: negro';
            } elseif ($totalPago > 0) {
                $proporcion = $pago['sueldo_blanco_pagado'] / $totalPago;
                $nota = null;
            } else {
                $proporcion = 0.0;
                $nota = 'Sin PLAME del mes: va como negro';
                if ($costo > 0) {
                    $sinPlame[$f->trabajador] = true;
                }
            }
            [$blanco, $negro] = CostosManoObraIndirectaReglas::separar($costo, $proporcion);
            $grupo = match ($f->origen_tipo) {
                'planilla_bono_productividad' => 'Bono de productividad',
                'mano_obra_indirecta' => $f->labor_nombre ?: 'Pagos sin horas',
                default => $f->labor_nombre ?: ($f->labor ? "Labor {$f->labor}" : 'Sin labor'),
            };
            $grupos[$grupo][] = $this->fila(Carbon::parse($f->fecha)->toDateString(), $f->trabajador, $grupo, round($f->minutos / 60, 2), null,
                $costo, $proporcion, $blanco, $negro, $nota);
        }
        if ($sinPlame) {
            $this->aviso(true, count($sinPlame) . ' trabajador(es) en FDM sin PLAME del mes (no se puede separar blanco/negro): ' . implode(', ', array_slice(array_keys($sinPlame), 0, 8)) . '.');
        }
        return $grupos;
    }

    // ------------------------------------------------------------------ cuadrilla

    private function cuadrilla(string $inicio, string $fin): array
    {
        $filas = DB::table('resumen_costo_diarios')->whereBetween('fecha', [$inicio, $fin])
            ->whereRaw('UPPER(campo) = ?', [CostosManoObraIndirectaReglas::CAMPO_FDM])
            ->whereIn('origen_tipo', ['cuadrilla', 'cuadrilla_bono'])
            ->orderBy('fecha')->get(['fecha', 'origen_tipo', 'labor', 'labor_nombre', 'trabajador', 'minutos', 'costo_total']);
        $sinCosto = $filas->filter(fn($f) => (float) $f->costo_total == 0 && $f->minutos > 0);
        if ($sinCosto->isNotEmpty()) {
            $this->aviso(false, $sinCosto->count() . ' registro(s) de cuadrilla en FDM con horas y costo 0 (revisar el jornal del grupo).');
        }
        $grupos = [];
        foreach ($filas as $f) {
            $grupo = ($f->origen_tipo === 'cuadrilla_bono' ? 'Bono: ' : '') . ($f->labor_nombre ?: ($f->labor ? "Labor {$f->labor}" : 'Sin labor'));
            $costo = (float) $f->costo_total;
            $grupos[$grupo][] = $this->fila(Carbon::parse($f->fecha)->toDateString(), $f->trabajador, $grupo, round($f->minutos / 60, 2), null,
                $costo, 0.0, 0.0, $costo, null);
        }
        return $grupos;
    }

    private function gastosGrupo(int $anio, int $mes): array
    {
        $gastos = GastoAdicionalPorGrupoCuadrilla::with('grupo')->where('anio_contable', $anio)->where('mes_contable', $mes)->orderBy('fecha_gasto')->get();
        $pendientes = $gastos->where('estado', '<>', 'aprobado');
        if ($pendientes->isNotEmpty()) {
            $this->aviso(true, $pendientes->count() . ' gasto(s) adicional(es) de grupos de cuadrilla sin aprobar (S/ ' . number_format($pendientes->sum('monto'), 2) . '): no se suman hasta aprobarlos.');
        }
        $grupos = [];
        foreach ($gastos->where('estado', 'aprobado') as $g) {
            $grupo = $g->grupo?->nombre ?? $g->codigo_grupo;
            $monto = (float) $g->monto;
            $grupos[$grupo][] = $this->fila($g->fecha_gasto ? Carbon::parse($g->fecha_gasto)->toDateString() : null, $g->descripcion, $grupo, null, null,
                $monto, 0.0, 0.0, $monto, 'Por confirmar si va a Servicios fundo');
        }
        return $grupos;
    }

    // ------------------------------------------------------------------ almacén

    private function combustible(string $inicio, string $fin): array
    {
        $salidas = AlmacenProductoSalida::with(['maquinaria', 'producto', 'distribuciones'])->whereNotNull('maquinaria_id')
            ->whereBetween('fecha_reporte', [$inicio, $fin])->orderBy('fecha_reporte')->get();
        $this->revisarKardex($salidas, 'combustible');
        if ($salidas->isEmpty()) {
            $this->aviso(true, 'No hay salidas de combustible de maquinaria registradas en el mes: ¿falta registrarlas?');
        }

        $pendientes = [];
        $grupos = [];
        foreach ($salidas as $s) {
            $maquina = $s->maquinaria?->nombre ?? "Máquina {$s->maquinaria_id}";
            if ($s->total_costo === null || !$s->movimiento_id) {
                $this->aviso(true, "Salida de combustible del {$s->fecha_reporte->format('d/m/Y')} ({$maquina}) sin costo de kardex: genera el kardex de combustible.");
                continue;
            }
            $costoSalida = (float) $s->total_costo;
            $proporcionBlanco = $s->tipo_kardex === 'blanco' ? 1.0 : 0.0;
            if (!$s->maquinaria?->usa_distribucion) {
                [$b, $n] = CostosManoObraIndirectaReglas::separar($costoSalida, $proporcionBlanco);
                $grupos[$maquina][] = $this->fila($s->fecha_reporte->toDateString(), ($s->producto?->nombre_comercial ?? 'Combustible') . " ({$s->tipo_kardex})", $maquina,
                    null, (float) $s->cantidad, $costoSalida, $proporcionBlanco, $b, $n, 'Máquina sin distribución: 100 % FDM');
                continue;
            }
            if ($s->distribuciones->isEmpty()) {
                $pendientes[] = "{$s->fecha_reporte->format('d/m')} {$maquina}";
                continue;
            }
            $horasTotal = (float) $s->distribuciones->sum('horas');
            $horasFdm = (float) $s->distribuciones->filter(fn($d) => CostosManoObraIndirectaReglas::esFdm($d->campo))->sum('horas');
            if ($horasFdm <= 0 || $horasTotal <= 0) {
                continue;
            }
            $ratio = $horasFdm / $horasTotal;
            $costo = round($costoSalida * $ratio, 2);
            [$b, $n] = CostosManoObraIndirectaReglas::separar($costo, $proporcionBlanco);
            $grupos[$maquina][] = $this->fila($s->fecha_reporte->toDateString(), ($s->producto?->nombre_comercial ?? 'Combustible') . " ({$s->tipo_kardex})", $maquina,
                round($horasFdm, 2), round((float) $s->cantidad * $ratio, 3), $costo, $proporcionBlanco, $b, $n,
                number_format($horasFdm, 2) . ' h FDM de ' . number_format($horasTotal, 2) . ' h distribuidas (' . number_format($ratio * 100, 1) . ' % de S/ ' . number_format($costoSalida, 2) . ')');
        }
        if ($pendientes) {
            $this->aviso(true, count($pendientes) . ' salida(s) de combustible de máquinas que usan distribución todavía sin distribuir (no se pueden contar): '
                . implode(', ', array_slice($pendientes, 0, 10)) . (count($pendientes) > 10 ? '…' : '') . '.');
        }
        return $grupos;
    }

    private function insumos(string $inicio, string $fin): array
    {
        $salidas = AlmacenProductoSalida::with(['producto.categoria'])->whereNull('maquinaria_id')
            ->whereRaw('UPPER(campo_nombre) = ?', [CostosManoObraIndirectaReglas::CAMPO_FDM])
            ->whereBetween('fecha_reporte', [$inicio, $fin])->orderBy('fecha_reporte')->get();
        $this->revisarKardex($salidas, 'insumos');

        $grupos = [];
        foreach ($salidas as $s) {
            if ($s->total_costo === null) {
                $this->aviso(true, "Salida de {$s->producto?->nombre_comercial} a FDM del {$s->fecha_reporte->format('d/m/Y')} sin costo de kardex.");
                continue;
            }
            $grupo = ucfirst($s->producto?->categoria_codigo ?? 'Sin categoría');
            $proporcionBlanco = $s->tipo_kardex === 'blanco' ? 1.0 : 0.0;
            $costo = (float) $s->total_costo;
            [$b, $n] = CostosManoObraIndirectaReglas::separar($costo, $proporcionBlanco);
            $grupos[$grupo][] = $this->fila($s->fecha_reporte->toDateString(), ($s->producto?->nombre_comercial ?? '-') . " ({$s->tipo_kardex})", $grupo,
                null, (float) $s->cantidad, $costo, $proporcionBlanco, $b, $n, null);
        }
        return $grupos;
    }

    /** Kardex de las salidas desactualizados → no concluyente (CostosManoObraIndirectaProceso los regenera antes de guardar). */
    private function revisarKardex(Collection $salidas, string $que): void
    {
        foreach ($salidas->groupBy(fn($s) => $s->producto_id . '|' . $s->tipo_kardex . '|' . $s->fecha_reporte->year) as $grupo) {
            $s = $grupo->first();
            if (!$s->tipo_kardex) {
                $this->aviso(true, "Salidas de {$que} ({$s->producto?->nombre_comercial}) sin almacén blanco/negro asignado.");
                continue;
            }
            $kardex = InsKardex::where('producto_id', $s->producto_id)->where('tipo', $s->tipo_kardex)->where('anio', $s->fecha_reporte->year)->first();
            $motivo = $kardex ? $this->kardex->motivoDesactualizado($kardex) : 'no existe el kardex';
            if ($motivo) {
                $this->aviso(true, "Kardex {$s->tipo_kardex} de {$s->producto?->nombre_comercial} {$s->fecha_reporte->year} desactualizado ({$motivo}). Al calcular se intenta regenerar.");
            }
        }
    }

    // ------------------------------------------------------------------ otros

    private function servicios(string $inicio, string $fin): array
    {
        $filas = DB::table('servicios_campo_detalles as d')->join('servicios_campo as s', 's.id', '=', 'd.servicio_campo_id')
            ->whereBetween('d.fecha', [$inicio, $fin])->whereRaw('UPPER(d.campo) = ?', [CostosManoObraIndirectaReglas::CAMPO_FDM])
            ->orderBy('d.fecha')->get(['d.fecha', 'd.labor', 'd.cantidad', 'd.costo', 's.servicio', 's.tipo_costo', 's.numero_comprobante']);
        $grupos = [];
        foreach ($filas as $f) {
            $proporcionBlanco = $f->tipo_costo === 'blanco' ? 1.0 : 0.0;
            $costo = (float) $f->costo;
            [$b, $n] = CostosManoObraIndirectaReglas::separar($costo, $proporcionBlanco);
            $grupos[$f->servicio][] = $this->fila(Carbon::parse($f->fecha)->toDateString(), trim(($f->labor ?? '') . ' ' . ($f->numero_comprobante ? "({$f->numero_comprobante})" : '')),
                $f->servicio, null, (float) $f->cantidad, $costo, $proporcionBlanco, $b, $n, null);
        }
        return $grupos;
    }

    private function otros(int $anio, int $mes): array
    {
        $filas = DB::table('costo_fdm_mensuals')->whereYear('fecha', $anio)->whereMonth('fecha', $mes)->orderBy('fecha')->get();
        $grupos = [];
        foreach ($filas as $f) {
            $b = (float) $f->monto_blanco;
            $n = (float) $f->monto_negro;
            $grupos[$f->destinatario ?: 'Sin destinatario'][] = $this->fila(Carbon::parse($f->fecha)->toDateString(), $f->descripcion, $f->destinatario,
                null, null, $b + $n, $b + $n > 0 ? $b / ($b + $n) : 0, $b, $n, null);
        }
        return $grupos;
    }

    // ------------------------------------------------------------------ armado

    private function fila(?string $fecha, ?string $detalle, ?string $grupo, ?float $horas, ?float $cantidad, float $costo,
        float $proporcionBlanco, float $blanco, float $negro, ?string $nota): array
    {
        // Cada fila en céntimos y separada igual que en el Excel (blanco = ROUND(costo × %); negro = el resto), para que
        // la suma de la pantalla y la del Excel sean idénticas
        $costo = round($costo, 2);
        [$blanco, $negro] = CostosManoObraIndirectaReglas::separar($costo, $proporcionBlanco);
        return compact('fecha', 'detalle', 'grupo', 'horas', 'cantidad', 'costo', 'blanco', 'negro', 'nota')
            + ['pct_blanco' => round($proporcionBlanco * 100, 2)];
    }

    /** @param array<string, array> $grupos grupo => filas */
    private function totalizar(array $grupos): array
    {
        $resultado = [];
        foreach ($grupos as $nombre => $filas) {
            $resultado[] = [
                'nombre' => (string) $nombre,
                'filas' => $filas,
                'horas' => round(array_sum(array_map(fn($f) => (float) $f['horas'], $filas)), 2),
                'blanco' => round(array_sum(array_column($filas, 'blanco')), 2),
                'negro' => round(array_sum(array_column($filas, 'negro')), 2),
                'total' => round(array_sum(array_column($filas, 'costo')), 2),
            ];
        }
        usort($resultado, fn($a, $b) => $b['total'] <=> $a['total']);
        return [
            'grupos' => $resultado,
            'blanco' => round(array_sum(array_column($resultado, 'blanco')), 2),
            'negro' => round(array_sum(array_column($resultado, 'negro')), 2),
            'total' => round(array_sum(array_column($resultado, 'total')), 2),
        ];
    }

    private function aviso(bool $noConcluyente, string $texto): void
    {
        $this->avisos[] = ['no_concluyente' => $noConcluyente, 'texto' => $texto];
    }
}
