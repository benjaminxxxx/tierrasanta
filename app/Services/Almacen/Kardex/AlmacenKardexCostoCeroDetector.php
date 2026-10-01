<?php

namespace App\Services\Almacen\Kardex;

use App\Models\CompraDetalle;
use App\Models\InsKardex;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tarea pendiente: kardex con entradas de compra valorizadas en 0 aunque la compra sí tiene monto.
 *
 * Pasa con los kardex generados antes de la migración 2026_10_01_100000 (compra_detalles.costo_total_kardex
 * quedó en 0 y el movimiento de stock lo copió). Regenerar el kardex toma el costo correcto de la compra y
 * corrige también el costo de sus salidas. Compras registradas con precio 0 no cuentan (no hay qué corregir).
 *
 * Tarea de estado (sin periodo): se ve en cualquier mes hasta que no quede ninguno.
 */
class AlmacenKardexCostoCeroDetector
{
    public const TIPO = 'kardex-costo-cero';

    public function __construct(
        private TareaPendienteServicio $registrador,
        private InsumoKardexMovimientosServicio $movimientos,
    ) {
    }

    /**
     * Kardex afectados: [kardex_id => cantidad de entradas en 0 que se pueden corregir].
     *
     * @return Collection<int, int>
     */
    public function kardexAfectados(): Collection
    {
        return DB::table('ins_kardex_movimientos as k')
            ->join('movimientos_stock as m', 'm.id', '=', 'k.stock_movimiento_id')
            ->join('compra_detalles as d', 'd.id', '=', 'm.origen_id')
            ->where('m.origen_type', CompraDetalle::class)
            ->where('k.tipo_mov', 'entrada')
            ->where('k.entrada_cantidad', '>', 0)
            ->where('k.entrada_costo_total', 0)
            ->where(fn($q) => $q->where('d.total_linea', '>', 0)->orWhere('d.costo_total_kardex', '>', 0))
            ->groupBy('k.kardex_id')
            ->selectRaw('k.kardex_id, COUNT(*) as entradas')
            ->pluck('entradas', 'kardex_id');
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $afectados = $this->kardexAfectados();
        $kardexes = InsKardex::whereIn('id', $afectados->keys())->orderBy('anio')->orderBy('descripcion')->get();

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Kardex con compras en costo 0',
            'descripcion' => 'Entradas de compra valorizadas en S/ 0 aunque la compra tiene monto (kardex generados antes de '
                . 'corregir costo_total_kardex). Regenerar el kardex toma el costo de la compra y recalcula el costo de sus '
                . 'salidas; después hay que volver a consolidar los costos de insumos de esos meses.',
            'variante' => 'danger',
            'cantidad_afectados' => $kardexes->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => $kardexes->isNotEmpty() ? [[
                'titulo' => 'Regenerar todos',
                'metodo' => 'regenerarTodos',
                'parametros' => [],
            ]] : [],
        ]);

        $vigentes = [];
        foreach ($kardexes as $kardex) {
            $clave = "kardex-{$kardex->id}";
            $vigentes[] = $clave;
            $entradas = (int) $afectados[$kardex->id];

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => "{$kardex->codigo_existencia} {$kardex->descripcion} — {$kardex->tipo} {$kardex->anio}",
                'descripcion' => "{$entradas} entrada(s) de compra con costo 0.",
                'variante' => 'danger',
                'cantidad_afectados' => $entradas,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Regenerar',
                    'metodo' => 'regenerar',
                    'parametros' => ['kardexId' => $kardex->id],
                ], [
                    'titulo' => 'Ver kardex',
                    'tipo_accion' => 'link',
                    'url' => route('almacen.kardex.detalle', ['insumoKardexId' => $kardex->id]),
                ]],
            ]);
        }

        // Kardex que ya se regeneraron (por esta tarea o desde su detalle): se cierran solos
        TareaPendiente::where('tipo', self::TIPO)
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }

    /** Acción "Regenerar" de una subtarea. */
    public function regenerar(int $kardexId): void
    {
        $this->movimientos->generarMovimientos(InsKardex::findOrFail($kardexId));
    }

    /**
     * Acción "Regenerar todos". Si alguno falla, sigue con los demás y al final informa cuáles no se pudieron.
     */
    public function regenerarTodos(): void
    {
        // ~2 s por kardex: con varias decenas puede pasar el max_execution_time del servidor
        @set_time_limit(600);
        $errores = [];
        foreach (InsKardex::whereIn('id', $this->kardexAfectados()->keys())->get() as $kardex) {
            try {
                $this->movimientos->generarMovimientos($kardex);
            } catch (\Throwable $e) {
                $errores[] = "{$kardex->descripcion} ({$kardex->tipo} {$kardex->anio}): " . $e->getMessage();
            }
        }
        if ($errores) {
            throw new \RuntimeException('No se pudieron regenerar: ' . implode(' | ', $errores));
        }
    }
}
