<?php

namespace App\Services\Caja\Oficina;

use App\Models\CajaArqueo;
use App\Models\CajaMovimiento;
use App\Models\CajaOficinaEnvio;
use App\Models\CajaOficinaMovimiento;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use Illuminate\Support\Carbon;

/**
 * Lecturas de la caja de oficina: sus filas con el disponible, los envíos a la caja de movimientos y el
 * cuadre mensual entre las dos cajas.
 */
class CajaOficinaConsulta
{
    /**
     * @param array{anio:int, mes:?int, buscar?:?string, estado?:?string} $filtros
     * @return array{filas: array, saldo_anterior: float, saldo_final: float, totales: array, desde: string, hasta: string}
     */
    public function listar(array $filtros): array
    {
        $anio = (int) $filtros['anio'];
        $mes = !empty($filtros['mes']) ? (int) $filtros['mes'] : null;
        $inicio = Carbon::create($anio, $mes ?: 1, 1);
        [$desde, $hasta] = [$inicio->toDateString(), ($mes ? $inicio->copy()->endOfMonth() : Carbon::create($anio, 12, 31))->toDateString()];

        $previo = (float) CajaOficinaMovimiento::where('fecha', '<', $desde)->sum('importe');
        $delPeriodo = CajaOficinaMovimiento::with('inverso:id,inverso_de_id')->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('orden')->orderBy('id')->get();

        $buscar = mb_strtolower(trim((string) ($filtros['buscar'] ?? '')));
        $estado = $filtros['estado'] ?? '';
        $disponible = $previo;
        $filas = [];
        foreach ($delPeriodo as $m) {
            $disponible += (float) $m->importe;
            $texto = mb_strtolower(implode(' ', [$m->beneficiario, $m->descripcion, $m->numero_documento, $m->tipo_documento, $m->categoria, $m->numero_caja]));
            if ($buscar !== '' && !str_contains($texto, $buscar)) {
                continue;
            }
            $esInverso = $m->inverso_de_id !== null;
            $efectoCero = $esInverso || $m->inverso !== null;
            if (($estado === 'pendiente' && !$m->pendiente_envio) || ($estado === 'cero' && !$efectoCero)) {
                continue;
            }
            $filas[] = [
                'id' => $m->id,
                'numero_caja' => $m->es_contable ? 'Contable' : $m->numero_caja,
                'condicion' => CajaMovimientoReglas::CONDICIONES[$m->condicion] ?? $m->condicion,
                'fecha' => $m->fecha->format('d/m/Y'),
                'beneficiario' => $m->beneficiario,
                'descripcion' => $m->descripcion,
                'categoria' => $m->categoria,
                'numero_documento' => $m->numero_documento,
                'importe' => (float) $m->importe,
                'importe_detalle' => $m->importe_detalle,
                'disponible' => round($disponible, 2),
                'es_saldo_inicial' => $m->es_saldo_inicial,
                'es_inverso' => $esInverso,
                'tiene_inverso' => $m->inverso !== null,
                'pendiente_envio' => $m->pendiente_envio,
                'enviado' => $m->enviado_at !== null,
            ];
        }

        $movimientos = $delPeriodo->where('es_saldo_inicial', false);
        $ingresos = $movimientos->filter(fn($m) => (float) $m->importe > 0)->sum(fn($m) => (float) $m->importe);
        $egresos = $movimientos->filter(fn($m) => (float) $m->importe < 0)->sum(fn($m) => (float) $m->importe);

        return [
            'filas' => $filas,
            'saldo_anterior' => round($previo + $delPeriodo->where('es_saldo_inicial', true)->sum(fn($m) => (float) $m->importe), 2),
            'saldo_final' => round($disponible, 2),
            'totales' => ['movimientos' => $delPeriodo->count(), 'ingresos' => round($ingresos, 2), 'egresos' => round($egresos, 2)],
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * Envíos con el detalle de cada fila, listos para mostrar (pendientes, o los últimos ya anexados).
     *
     * @return array<int, array<string, mixed>>
     */
    public function envios(bool $soloPendientes = true, int $limite = 10): array
    {
        return CajaOficinaEnvio::with('detalles')
            ->when($soloPendientes, fn($q) => $q->where('estado', CajaOficinaEnvio::PENDIENTE)->orderBy('id'),
                fn($q) => $q->orderByDesc('id')->limit($limite))
            ->get()
            ->map(fn(CajaOficinaEnvio $e) => [
                'id' => $e->id,
                'estado' => $e->estado,
                'quien' => $e->enviado_nombre ?? '—',
                'fecha' => $e->created_at?->format('d/m/Y H:i'),
                'nota' => $e->nota,
                'cambios' => $e->cambios,
                'anexado' => $e->anexado_at ? $e->anexado_at->format('d/m/Y H:i') . ' · ' . ($e->anexado_nombre ?? '—') : null,
                'detalles' => $e->detalles->map(fn($d) => [
                    'accion' => $d->accion,
                    'es_inverso' => (bool) ($d->datos['es_inverso'] ?? false),
                    'fecha' => Carbon::parse($d->datos['fecha'])->format('d/m/Y'),
                    'numero_caja' => !empty($d->datos['es_contable']) ? 'Contable' : ($d->datos['numero_caja'] ?? null),
                    'beneficiario' => $d->datos['beneficiario'] ?? null,
                    'descripcion' => $d->datos['descripcion'] ?? null,
                    'importe' => (float) $d->datos['importe'],
                    'cambios' => $d->accion === 'modificado' ? $this->diferencias($d->datos_antes ?? [], $d->datos) : [],
                    'motivo' => $d->accion === 'eliminado' ? ($d->datos['motivo_eliminacion'] ?? null) : null,
                    'resultado' => $d->resultado,
                    'resultado_nota' => $d->resultado_nota,
                ])->all(),
            ])->all();
    }

    /**
     * Cuadre del mes entre las dos cajas. La caja de movimientos tiene más que la de oficina: lo que está
     * en otras fuentes (campo, naranja, cuadrillas…) según el último arqueo del mes. Restado eso, deben
     * quedar iguales.
     *
     * @return array{iniciada:bool, movimientos:float, otras_fuentes:float, fuentes:array, arqueo_fecha:?string, esperado:float,
     *               oficina:float, diferencia:float, cuadra:bool, por_enviar:int, envios_pendientes:int}
     */
    public function cuadre(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1);
        [$desde, $hasta] = [$inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString()];

        $movimientos = round((float) CajaMovimiento::where('fecha', '<=', $hasta)->sum('importe'), 2);
        $oficina = round((float) CajaOficinaMovimiento::where('fecha', '<=', $hasta)->sum('importe'), 2);

        $arqueo = CajaArqueo::with('detalles.fuente')->whereBetween('fecha', [$desde, $hasta])->orderByDesc('fecha')->orderByDesc('id')->first();
        $fuentes = ($arqueo?->detalles ?? collect())->filter(fn($d) => !$d->fuente?->es_oficina)
            ->map(fn($d) => ['fuente' => $d->fuente?->nombre, 'monto' => (float) $d->monto])->values()->all();
        $otras = round(array_sum(array_column($fuentes, 'monto')), 2);
        $esperado = round($movimientos - $otras, 2);

        return [
            'iniciada' => CajaOficinaMovimiento::exists(),
            'movimientos' => $movimientos,
            'otras_fuentes' => $otras,
            'fuentes' => $fuentes,
            'arqueo_fecha' => $arqueo?->fecha->format('d/m/Y'),
            'esperado' => $esperado,
            'oficina' => $oficina,
            'diferencia' => round($oficina - $esperado, 2),
            'cuadra' => abs($oficina - $esperado) < 0.005,
            'por_enviar' => CajaOficinaMovimiento::withTrashed()->where('pendiente_envio', true)->whereBetween('fecha', [$desde, $hasta])
                ->where(fn($q) => $q->whereNull('deleted_at')->orWhereNotNull('enviado_at'))->count(),
            'envios_pendientes' => CajaOficinaEnvio::where('estado', CajaOficinaEnvio::PENDIENTE)->count(),
        ];
    }

    /** @return array<int, array{campo:string, antes:mixed, despues:mixed}> */
    private function diferencias(array $antes, array $despues): array
    {
        $etiquetas = ['numero_caja' => 'N° caja', 'es_contable' => 'Contable', 'condicion' => 'Condición', 'categoria' => 'Categoría',
            'codigo' => 'Código', 'beneficiario' => 'Beneficiario', 'descripcion' => 'Gastos B+N', 'fecha' => 'Fecha', 'semana' => 'Semana',
            'tipo_documento' => 'T. Doc', 'numero_documento' => 'N° Doc', 'situacion_cheque' => 'Situación cheque', 'importe_usd' => 'Importe $',
            'tipo_cambio_operacion' => 'TC del pago', 'importe' => 'Importe S/', 'importe_detalle' => 'Operación', 'tipo_cambio' => 'TC'];
        $lista = [];
        foreach ($etiquetas as $campo => $etiqueta) {
            if (($antes[$campo] ?? null) != ($despues[$campo] ?? null)) {
                $lista[] = ['campo' => $etiqueta, 'antes' => $antes[$campo] ?? null, 'despues' => $despues[$campo] ?? null];
            }
        }
        return $lista;
    }
}
