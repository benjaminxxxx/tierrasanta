<?php

namespace App\Services\Caja\Movimiento;

use App\Models\CajaArqueo;
use App\Models\CajaClasificador;
use App\Models\CajaMovimiento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos de caja para la tabla y el Excel: filas con su disponible (acumulado desde el primer
 * movimiento, en orden de fecha y orden del día), saldo anterior del periodo y totales.
 *
 * El disponible siempre es el de la caja completa: con filtros de texto o de clasificador se muestran
 * solo algunas filas, pero cada una conserva su disponible real.
 */
class CajaMovimientoConsulta
{
    /**
     * @param array{anio?:int, mes?:?int, semana?:?int, tipo?:?string, condicion?:?string, contable?:?string,
     *              clasificador_1?:?string, clasificador_2?:?string, subgrupo?:?string, buscar?:?string} $filtros
     * @return array{filas: array, saldo_anterior: float, saldo_final: float, totales: array, desde: string, hasta: string}
     */
    public function listar(array $filtros): array
    {
        [$desde, $hasta] = $this->rango($filtros);

        $saldoAnterior = (float) CajaMovimiento::where('fecha', '<', $desde)->sum('importe');

        // Disponible: acumulado de todos los movimientos del periodo (sin filtros), partiendo del saldo anterior
        $delPeriodo = CajaMovimiento::whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('orden')->orderBy('id')
            ->get();
        $disponible = $saldoAnterior;
        $disponiblePorId = [];
        foreach ($delPeriodo as $m) {
            $disponible += (float) $m->importe;
            $disponiblePorId[$m->id] = round($disponible, 2);
        }

        $filtrados = $delPeriodo->filter(fn($m) => $this->cumple($m, $filtros));
        $clasificadores = CajaClasificador::get()->keyBy('id');

        $filas = $filtrados->values()->map(function (CajaMovimiento $m) use ($disponiblePorId, $clasificadores) {
            $clasificador = $clasificadores->get($m->caja_clasificador_id);
            $estilo = CajaMovimientoReglas::estilo($m->only(['color_fondo', 'color_texto', 'negrita', 'subgrupo_ng']), $clasificador?->toArray());
            $importe = (float) $m->importe;
            $tc = $m->tipo_cambio ? (float) $m->tipo_cambio : null;
            return [
                'id' => $m->id,
                'empresa' => $m->empresa,
                'numero_caja' => $m->es_contable ? 'Contable' : $m->numero_caja,
                'condicion' => CajaMovimientoReglas::CONDICIONES[$m->condicion] ?? $m->condicion,
                'categoria' => $m->categoria,
                'codigo' => $m->codigo,
                'beneficiario' => $m->beneficiario,
                'descripcion' => $m->descripcion,
                'clasificador_1' => $m->clasificador_1,
                'clasificador_2' => $m->clasificador_2,
                'subgrupo_ng' => $m->subgrupo_ng,
                'subgrupo_bl' => $m->subgrupo_bl,
                'moneda' => $m->moneda,
                'fecha' => $m->fecha->toDateString(),
                'semana' => CajaMovimientoReglas::etiquetaSemana($m->semana),
                'tipo_documento' => $m->tipo_documento,
                'numero_documento' => $m->numero_documento,
                'situacion_cheque' => $m->situacion_cheque,
                'importe_usd' => $m->importe_usd !== null ? (float) $m->importe_usd : null,
                'importe' => $importe,
                'importe_detalle' => $m->importe_detalle,
                'disponible' => $disponiblePorId[$m->id],
                'tipo' => $importe >= 0 ? 'INGRESO' : 'EGRESO',
                'anio' => $m->fecha->year,
                'mes' => $m->fecha->month,
                'semana_anio' => (int) $m->fecha->format('W'),
                'dolarizado' => $m->importe_usd !== null ? (float) $m->importe_usd : ($tc ? round($importe / $tc, 2) : null),
                'tipo_cambio' => $tc,
                'estilo' => $estilo,
            ];
        })->all();

        $ingresos = $filtrados->filter(fn($m) => (float) $m->importe > 0)->sum(fn($m) => (float) $m->importe);
        $egresos = $filtrados->filter(fn($m) => (float) $m->importe < 0)->sum(fn($m) => (float) $m->importe);

        return [
            'filas' => $filas,
            'saldo_anterior' => round($saldoAnterior, 2),
            'saldo_final' => round($disponible, 2),
            'totales' => [
                'movimientos' => count($filas),
                'ingresos' => round($ingresos, 2),
                'egresos' => round($egresos, 2),
                'neto' => round($ingresos + $egresos, 2),
            ],
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /** Disponible al cierre de una fecha (incluye todos sus movimientos). */
    public function disponibleAl($fecha): float
    {
        return round((float) CajaMovimiento::where('fecha', '<=', Carbon::parse($fecha)->toDateString())->sum('importe'), 2);
    }

    /**
     * Arqueos del periodo con su total por fuente y la diferencia contra el disponible del día.
     *
     * @return array<int, array{id:int, fecha:string, observacion:?string, detalles:array, total:float, disponible:float, diferencia:float}>
     */
    public function arqueos(string $desde, string $hasta): array
    {
        return CajaArqueo::with('detalles.fuente')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get()
            ->map(function (CajaArqueo $a) {
                $total = round((float) $a->detalles->sum('monto'), 2);
                $disponible = $this->disponibleAl($a->fecha);
                return [
                    'id' => $a->id,
                    'fecha' => $a->fecha->toDateString(),
                    'observacion' => $a->observacion,
                    'detalles' => $a->detalles->sortBy(fn($d) => $d->fuente?->orden)->map(fn($d) => [
                        'fuente' => $d->fuente?->nombre, 'caja_fuente_id' => $d->caja_fuente_id, 'monto' => (float) $d->monto,
                    ])->values()->all(),
                    'total' => $total,
                    'disponible' => $disponible,
                    'diferencia' => round($total - $disponible, 2),
                ];
            })->all();
    }

    /** Totales por clasificador del periodo (para el resumen y el Excel). */
    public function totalesPorClasificador(string $desde, string $hasta): array
    {
        return CajaMovimiento::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('clasificador_1, clasificador_2, SUM(CASE WHEN importe > 0 THEN importe ELSE 0 END) as ingresos,
                SUM(CASE WHEN importe < 0 THEN importe ELSE 0 END) as egresos, COUNT(*) as movimientos')
            ->groupBy('clasificador_1', 'clasificador_2')
            ->orderBy('clasificador_1')->orderBy('clasificador_2')
            ->get()
            ->map(fn($r) => [
                'clasificador_1' => $r->clasificador_1, 'clasificador_2' => $r->clasificador_2,
                'ingresos' => round((float) $r->ingresos, 2), 'egresos' => round((float) $r->egresos, 2),
                'movimientos' => (int) $r->movimientos,
            ])->all();
    }

    /** Valores para los filtros y para sugerir en el formulario. */
    public function opciones(): array
    {
        $distintos = fn(string $col) => CajaMovimiento::whereNotNull($col)->where($col, '<>', '')
            ->distinct()->orderBy($col)->pluck($col)->all();

        return [
            'anios' => CajaMovimiento::selectRaw('DISTINCT YEAR(fecha) as anio')->orderByDesc('anio')->pluck('anio')->all() ?: [now()->year],
            'clasificadores' => CajaClasificador::where('activo', true)->orderBy('tipo')->orderBy('orden')->orderBy('clasificador_1')->orderBy('clasificador_2')
                ->get(['id', 'tipo', 'grupo', 'clasificador_1', 'clasificador_2'])->toArray(),
            'subgrupos_ng' => $distintos('subgrupo_ng'),
            'subgrupos_bl' => $distintos('subgrupo_bl'),
            'beneficiarios' => $distintos('beneficiario'),
            'categorias' => $distintos('categoria'),
        ];
    }

    /** @return array{0:string, 1:string} */
    public function rango(array $filtros): array
    {
        $anio = (int) ($filtros['anio'] ?? now()->year);
        $mes = !empty($filtros['mes']) ? (int) $filtros['mes'] : null;
        $inicio = $mes ? Carbon::create($anio, $mes, 1) : Carbon::create($anio, 1, 1);
        $fin = $mes ? $inicio->copy()->endOfMonth() : Carbon::create($anio, 12, 31);
        return [$inicio->toDateString(), $fin->toDateString()];
    }

    private function cumple(CajaMovimiento $m, array $f): bool
    {
        if (!empty($f['semana']) && (int) $m->semana !== (int) $f['semana']) {
            return false;
        }
        if (!empty($f['tipo']) && $m->tipo !== $f['tipo']) {
            return false;
        }
        if (!empty($f['condicion']) && $m->condicion !== $f['condicion']) {
            return false;
        }
        if (($f['contable'] ?? '') === 'si' && !$m->es_contable) {
            return false;
        }
        if (($f['contable'] ?? '') === 'no' && $m->es_contable) {
            return false;
        }
        if (!empty($f['clasificador_1']) && $m->clasificador_1 !== $f['clasificador_1']) {
            return false;
        }
        if (!empty($f['clasificador_2']) && $m->clasificador_2 !== $f['clasificador_2']) {
            return false;
        }
        if (!empty($f['subgrupo']) && $m->subgrupo_ng !== $f['subgrupo'] && $m->subgrupo_bl !== $f['subgrupo']) {
            return false;
        }
        if (!empty($f['buscar'])) {
            $q = mb_strtolower(trim($f['buscar']));
            $texto = mb_strtolower(implode(' ', [$m->beneficiario, $m->descripcion, $m->numero_documento, $m->tipo_documento,
                $m->situacion_cheque, $m->categoria, $m->codigo, $m->numero_caja]));
            if (!str_contains($texto, $q)) {
                return false;
            }
        }
        return true;
    }
}
