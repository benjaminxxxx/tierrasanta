<?php

namespace App\Services\Campo;

use App\Models\CampoCampania;
use App\Models\LaborServicio;
use App\Models\ServicioCampo;
use App\Models\ServicioCampoDetalle;
use App\Models\ServicioNombre;
use App\Services\Reporte\AuditoriaServicio;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Registro de servicios externos por campo (maquinaria con personal y combustible propios).
 *
 * Regla de costo: el costo asignado a los campos es SIEMPRE el total pagado (con IGV), porque la
 * empresa vende cochinilla exonerada de IGV y no tiene crédito fiscal que recuperar.
 * El costo_unitario se registra como figura en el comprobante: sin IGV en factura, con IGV en los demás.
 */
class ServicioCampoServicio
{
    /**
     * Busca la campaña vigente del campo en la fecha dada.
     * Devuelve null si el campo no tiene campaña en esa fecha.
     */
    public function resolverCampania(?string $campo, ?string $fecha): ?array
    {
        if (!$campo || !$fecha) {
            return null;
        }

        $campania = CampoCampania::where('campo', $campo)
            ->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha);
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        if (!$campania) {
            return null;
        }

        return [
            'id' => $campania->id,
            'nombre' => $campania->nombre_campania,
            'fecha_inicio' => Carbon::parse($campania->fecha_inicio)->format('d/m/Y'),
            'fecha_fin' => $campania->fecha_fin ? Carbon::parse($campania->fecha_fin)->format('d/m/Y') : null,
        ];
    }

    /**
     * Crea o actualiza un servicio con sus detalles. Los detalles se reemplazan completos
     * y los costos se recalculan (prorrateo exacto del total pagado).
     *
     * $datos: servicio, unidad, tipo_comprobante, numero_comprobante, tipo_costo,
     *         fecha_comprobante, porcentaje_igv, costo_unitario, subtotal, total,
     *         detalles[] => [fecha, campo, labor, cantidad]
     */
    public function guardar(array $datos, ?int $servicioId = null): ServicioCampo
    {
        $detalles = collect($datos['detalles'] ?? [])
            ->map(fn($d) => [
                'fecha' => $d['fecha'] ?? null,
                'campo' => $d['campo'] ?? null,
                'labor' => trim((string) ($d['labor'] ?? '')),
                'cantidad' => (float) ($d['cantidad'] ?? 0),
            ])
            ->values();

        if ($detalles->isEmpty()) {
            throw new Exception('Debe registrar al menos un campo trabajado.');
        }

        $errores = [];
        foreach ($detalles as $i => $d) {
            $fila = $i + 1;
            if (!$d['fecha'] || !$d['campo'] || $d['labor'] === '' || $d['cantidad'] <= 0) {
                $errores[] = "Fila {$fila}: complete fecha, campo, labor y una cantidad mayor a 0.";
                continue;
            }
            $campania = $this->resolverCampania($d['campo'], $d['fecha']);
            if (!$campania) {
                $errores[] = "Fila {$fila}: el campo {$d['campo']} no tiene campaña vigente al " . Carbon::parse($d['fecha'])->format('d/m/Y') . '.';
            }
            $detalles[$i] = $d + ['campania_id' => $campania['id'] ?? null];
        }
        if ($errores) {
            throw new Exception(implode(' ', $errores));
        }

        $esFactura = $datos['tipo_comprobante'] === 'factura';
        $tasa = ((float) ($datos['porcentaje_igv'] ?? 18)) / 100;
        $cantidadTotal = $detalles->sum('cantidad');

        // Monto en la base del costo unitario del comprobante: subtotal en factura, total en los demás.
        $montoComprobante = round((float) ($esFactura ? ($datos['subtotal'] ?? 0) : ($datos['total'] ?? 0)), 2);
        if ($montoComprobante <= 0) {
            $montoComprobante = round($cantidadTotal * (float) $datos['costo_unitario'], 2);
        }
        if ($montoComprobante <= 0) {
            throw new Exception('El monto del servicio debe ser mayor a 0.');
        }

        if ($esFactura) {
            $subtotal = $montoComprobante;
            $total = round($subtotal * (1 + $tasa), 2);
        } else {
            $total = $montoComprobante;
            $subtotal = round($total / (1 + $tasa), 2);
        }

        // Sin crédito fiscal: el IGV también es gasto, se reparte el total pagado.
        $costos = $this->prorratear($total, $detalles->pluck('cantidad')->all());

        return DB::transaction(function () use ($datos, $servicioId, $detalles, $costos, $cantidadTotal, $montoComprobante, $subtotal, $total) {
            $cabecera = [
                'servicio' => trim($datos['servicio']),
                'unidad' => trim($datos['unidad']),
                'costo_unitario' => (string) round($montoComprobante / $cantidadTotal, 6),
                'tipo_comprobante' => $datos['tipo_comprobante'],
                'numero_comprobante' => ($datos['numero_comprobante'] ?? null) ?: null,
                'tipo_costo' => $datos['tipo_costo'],
                'fecha_comprobante' => $datos['fecha_comprobante'],
                'porcentaje_igv' => (string) ($datos['porcentaje_igv'] ?? 18),
                'cantidad_total' => (string) $cantidadTotal,
                'subtotal' => (string) $subtotal,
                'igv' => (string) round($total - $subtotal, 2),
                'total' => (string) $total,
                'costo_total' => (string) $total,
            ];

            if ($servicioId) {
                $servicio = ServicioCampo::with('detalles')->findOrFail($servicioId);
                $antes = $this->snapshot($servicio);
                $servicio->update($cabecera + ['actualizado_por' => auth()->id()]);
                $servicio->detalles()->delete();
            } else {
                $antes = null;
                $servicio = ServicioCampo::create($cabecera + ['creado_por' => auth()->id()]);
            }

            foreach ($detalles as $i => $d) {
                $servicio->detalles()->create([
                    'fecha' => $d['fecha'],
                    'campo' => $d['campo'],
                    'campania_id' => $d['campania_id'],
                    'labor' => $d['labor'],
                    'cantidad' => (string) $d['cantidad'],
                    'costo' => (string) $costos[$i],
                ]);
                LaborServicio::registrarSiNoExiste($d['labor']);
            }
            ServicioNombre::registrarSiNoExiste($servicio->servicio);

            $servicio->load('detalles');
            AuditoriaServicio::registrar(
                ServicioCampo::class,
                $servicio->id,
                $servicioId ? 'editar' : 'crear',
                $antes,
                $this->snapshot($servicio),
                null,
                ['created_at', 'updated_at']
            );

            return $servicio;
        });
    }

    /**
     * Elimina el servicio y sus detalles, dejando el registro completo en auditorías.
     */
    public function eliminar(int $servicioId): void
    {
        DB::transaction(function () use ($servicioId) {
            $servicio = ServicioCampo::with('detalles')->findOrFail($servicioId);

            AuditoriaServicio::registrar(
                ServicioCampo::class,
                $servicio->id,
                'eliminar',
                $this->snapshot($servicio),
                null,
                "Servicio {$servicio->servicio} eliminado"
            );

            $servicio->delete(); // los detalles se eliminan por cascade
        });
    }

    /**
     * Intenta vincular a una campaña los detalles que quedaron sin campaña
     * (por ejemplo, porque la campaña se eliminó). Devuelve [vinculados, pendientes].
     */
    public function reasignarCampaniasHuerfanas(): array
    {
        $vinculados = 0;
        $pendientes = 0;

        ServicioCampoDetalle::whereNull('campania_id')->get()->each(function ($detalle) use (&$vinculados, &$pendientes) {
            $campania = $this->resolverCampania($detalle->campo, $detalle->fecha->format('Y-m-d'));
            if ($campania) {
                $detalle->update(['campania_id' => $campania['id']]);
                $vinculados++;
            } else {
                $pendientes++;
            }
        });

        return [$vinculados, $pendientes];
    }

    /**
     * Tabla de doble entrada: labores x meses (1..12) con el costo del año.
     * $tipoCosto: null (todos), 'blanco' o 'negro'.
     */
    public function resumenAnual(int $anio, ?string $tipoCosto = null): array
    {
        $filas = ServicioCampoDetalle::query()
            ->join('servicios_campo', 'servicios_campo.id', '=', 'servicios_campo_detalles.servicio_campo_id')
            ->whereYear('servicios_campo_detalles.fecha', $anio)
            ->when($tipoCosto, fn($q) => $q->where('servicios_campo.tipo_costo', $tipoCosto))
            ->selectRaw('servicios_campo_detalles.labor as labor, MONTH(servicios_campo_detalles.fecha) as mes, SUM(servicios_campo_detalles.costo) as costo')
            ->groupBy('servicios_campo_detalles.labor', DB::raw('MONTH(servicios_campo_detalles.fecha)'))
            ->orderBy('labor')
            ->get();

        $meses = array_fill(1, 12, 0.0);
        $labores = [];
        foreach ($filas as $fila) {
            $labores[$fila->labor] ??= $meses;
            $labores[$fila->labor][(int) $fila->mes] = round((float) $fila->costo, 2);
        }

        $totalesMes = $meses;
        foreach ($labores as $valores) {
            foreach ($valores as $mes => $costo) {
                $totalesMes[$mes] += $costo;
            }
        }

        return [
            'labores' => collect($labores)->map(fn($valores, $labor) => [
                'labor' => $labor,
                'meses' => array_values($valores),
                'total' => round(array_sum($valores), 2),
            ])->values()->all(),
            'totales_mes' => array_values(array_map(fn($v) => round($v, 2), $totalesMes)),
            'total_anual' => round(array_sum($totalesMes), 2),
        ];
    }

    /**
     * Reparte el monto proporcionalmente a las cantidades; la diferencia de redondeo
     * se asigna a la última fila para que la suma sea exacta.
     */
    private function prorratear(float $monto, array $cantidades): array
    {
        $total = array_sum($cantidades);
        $costos = [];
        $acumulado = 0;
        $ultimo = count($cantidades) - 1;

        foreach ($cantidades as $i => $cantidad) {
            if ($i === $ultimo) {
                $costos[$i] = round($monto - $acumulado, 2);
            } else {
                $costos[$i] = round($monto * $cantidad / $total, 2);
                $acumulado += $costos[$i];
            }
        }

        return $costos;
    }

    private function snapshot(ServicioCampo $servicio): array
    {
        $datos = $servicio->only([
            'servicio', 'unidad', 'costo_unitario', 'tipo_comprobante', 'numero_comprobante',
            'tipo_costo', 'porcentaje_igv', 'cantidad_total', 'subtotal', 'igv', 'total', 'costo_total',
        ]);
        $datos['fecha_comprobante'] = $servicio->fecha_comprobante?->format('Y-m-d');
        // Se serializa como texto para que el diff de auditoría pueda comparar los detalles.
        $datos['detalles'] = json_encode($servicio->detalles->map(fn($d) => [
            'fecha' => $d->fecha?->format('Y-m-d'),
            'campo' => $d->campo,
            'campania_id' => $d->campania_id,
            'labor' => $d->labor,
            'cantidad' => (float) $d->cantidad,
            'costo' => (float) $d->costo,
        ])->values(), JSON_UNESCAPED_UNICODE);

        return $datos;
    }
}
