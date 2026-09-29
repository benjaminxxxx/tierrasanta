<?php
// app/Services/StockService.php
namespace App\Services\Almacen;

use App\Models\InsKardex;
use App\Models\InsKardexMovimiento;
use App\Models\StockProducto;
use App\Models\MovimientoStock;
use App\Models\Almacen;
use App\Models\TransferenciaAlmacen;
use App\Services\Almacen\AlmacenPrincipalServicio;
use App\Support\DateHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Almacen\Kardex\KardexActualizacionServicio;

class StockService
{
    /**
     * Registra un movimiento real (entrada o salida) Y actualiza
     * el saldo materializado en el mismo paso, atómicamente.
     */
    /*
    public function registrarMovimiento(
        string $direccion,
        int $productoId,
        int $almacenId,
        float $cantidad,
        string $fechaMovimiento,
        string $tipoKardex,
        ?string $origenType = null,
        ?int $origenId = null,
        array $extra = []
    ): MovimientoStock {

        $vigente = DateHelper::esPeriodoVigente($fechaMovimiento);


        return DB::transaction(function () use ($direccion, $productoId, $almacenId, $cantidad, $fechaMovimiento, $tipoKardex, $origenType, $origenId, $extra,$vigente) {

            // Validación preventiva en salidas manuales
            if ($direccion === 'salida') {
                $disponible = self::disponible($productoId, $almacenId, $tipoKardex);
                if ($cantidad > $disponible) {
                    throw new \RuntimeException("Stock insuficiente. Disponible: {$disponible}, solicitado: {$cantidad}.");
                }
            }

            $movimiento = MovimientoStock::create(array_merge([
                'direccion' => $direccion,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
                'cantidad' => $cantidad,
                'fecha_movimiento' => $fechaMovimiento,
                'tipo_kardex' => $tipoKardex,
                'origen_type' => $origenType,
                'origen_id' => $origenId,
            ], $extra));

            $stock = StockProducto::firstOrCreate(
                ['producto_id' => $productoId, 'almacen_id' => $almacenId, 'tipo_kardex' => $tipoKardex],
                ['cantidad' => 0]
            );

            $delta = $direccion === 'entrada' ? $cantidad : -$cantidad;
            $stock->increment('cantidad', $delta);

            return $movimiento;
        });
    }*/
    public function registrarMovimiento(
        string $direccion,
        int $productoId,
        int $almacenId,
        float $cantidad,
        string $fechaMovimiento,
        string $tipoKardex,
        ?string $origenType = null,
        ?int $origenId = null,
        array $extra = []
    ): MovimientoStock {
        // No se agregan compras/salidas a un periodo cuyo kardex ya se cerró
        self::verificarKardexAbierto($productoId, $tipoKardex, $fechaMovimiento);

        $vigente = DateHelper::esPeriodoVigente($fechaMovimiento);

        return DB::transaction(function () use ($direccion, $productoId, $almacenId, $cantidad, $fechaMovimiento, $tipoKardex, $origenType, $origenId, $extra, $vigente) {

            $stock = StockProducto::firstOrCreate(
                ['producto_id' => $productoId, 'almacen_id' => $almacenId, 'tipo_kardex' => $tipoKardex],
                ['cantidad' => 0]
            );
            $stock = StockProducto::where('id', $stock->id)->lockForUpdate()->first();

            if ($vigente) {
                $anio = (int) date('Y', strtotime($fechaMovimiento));

                // Único query "extra" real, y solo corre para movimientos vigentes —
                // rango de fecha (sargable), no whereYear() (eso sí sería pesado sin índice funcional).
                $esPrimerMovimientoDelAnio = !MovimientoStock::where('producto_id', $productoId)
                    ->where('tipo_kardex', $tipoKardex)
                    ->whereBetween('fecha_movimiento', ["{$anio}-01-01", "{$anio}-12-31"])
                    ->exists();

                if ($esPrimerMovimientoDelAnio) {
                    $existeKardexDelAnio = InsKardex::where('producto_id', $productoId)
                        ->where('tipo', $tipoKardex)
                        ->where('anio', $anio)
                        ->exists();

                    if (!$existeKardexDelAnio) {
                        // Año nuevo sin apertura formal: no se hereda en silencio el saldo
                        // del año anterior (pudo quedar mal por no haberse cerrado). Se
                        // resetea a 0 para que cualquier desfase se vea como negativo,
                        // en vez de arrastrar un número que nadie confirmó.
                        $stock->update(['cantidad' => 0]);
                    }
                }
            }

            // Sin validación bloqueante en ningún caso — vigente o histórico.
            // El stock negativo es la señal, no un error a impedir.

            $movimiento = MovimientoStock::create(array_merge([
                'direccion' => $direccion,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
                'cantidad' => $cantidad,
                'fecha_movimiento' => $fechaMovimiento,
                'tipo_kardex' => $tipoKardex,
                'origen_type' => $origenType,
                'origen_id' => $origenId,
                //'afecta_stock_vigente' => $vigente,
            ], $extra));

            if ($vigente) {
                $delta = $direccion === 'entrada' ? $cantidad : -$cantidad;
                $stock->increment('cantidad', $delta);
            }
            // si no es vigente: el movimiento queda registrado para que el Kardex de
            // ESE año lo recoja al generarse, y StockProducto de hoy no se toca.

            return $movimiento;
        });
    }

    /**
     * Única regla de bloqueo: un movimiento no se crea, modifica ni elimina si el kardex de su
     * producto + tipo + año está CERRADO. Un kardex activo se deja modificar y queda
     * desactualizado (KardexActualizacionServicio lo detecta y lo regenera al consolidar).
     */
    public static function verificarKardexAbierto(int $productoId, string $tipoKardex, $fecha): void
    {
        $anio = (int) date('Y', strtotime((string) $fecha));

        $cerrado = InsKardex::with('producto')
            ->where('producto_id', $productoId)
            ->where('tipo', $tipoKardex)
            ->where('anio', $anio)
            ->where('estado', 'cerrado')
            ->first();

        if ($cerrado) {
            $nombre = $cerrado->producto?->nombre_comercial ?? "producto #{$productoId}";
            throw new \RuntimeException(
                "El kardex {$tipoKardex} {$anio} de {$nombre} está cerrado: no se pueden registrar ni modificar " .
                "compras o salidas de ese periodo. Reabre el kardex si necesitas corregirlo."
            );
        }
    }

    /**
     * Actualiza EN SU LUGAR el movimiento de un origen (línea de compra o salida), conservando su id.
     * Solo ajusta stock si cambia algo que lo afecta (producto, almacén, tipo, cantidad o fecha);
     * si solo cambia el costo, actualiza el costo sin tocar el stock. Si no cambia nada, no toca
     * el movimiento. Si el origen aún no tenía movimiento, lo registra.
     *
     * @param array $nuevos direccion?, producto_id, almacen_id, cantidad, fecha_movimiento, tipo_kardex, costo_unitario?, costo_total?
     */
    public function actualizarMovimientoDeOrigen(string $origenType, int $origenId, array $nuevos): MovimientoStock
    {
        $movimiento = MovimientoStock::where('origen_type', $origenType)->where('origen_id', $origenId)->first();

        if (!$movimiento) {
            return $this->registrarMovimiento(
                $nuevos['direccion'],
                (int) $nuevos['producto_id'],
                (int) $nuevos['almacen_id'],
                (float) $nuevos['cantidad'],
                (string) $nuevos['fecha_movimiento'],
                $nuevos['tipo_kardex'],
                $origenType,
                $origenId,
                array_intersect_key($nuevos, array_flip(['costo_unitario', 'costo_total']))
            );
        }

        return $this->actualizarMovimiento($movimiento, $nuevos);
    }

    public function actualizarMovimiento(MovimientoStock $mov, array $nuevos): MovimientoStock
    {
        $fechaNueva = Carbon::parse($nuevos['fecha_movimiento'] ?? $mov->fecha_movimiento)->toDateString();
        $destino = [
            'producto_id' => (int) ($nuevos['producto_id'] ?? $mov->producto_id),
            'almacen_id' => (int) ($nuevos['almacen_id'] ?? $mov->almacen_id),
            'tipo_kardex' => $nuevos['tipo_kardex'] ?? $mov->tipo_kardex,
            'cantidad' => (float) ($nuevos['cantidad'] ?? $mov->cantidad),
            'fecha_movimiento' => $fechaNueva,
        ];

        $afectaStock = $destino['producto_id'] !== (int) $mov->producto_id
            || $destino['almacen_id'] !== (int) $mov->almacen_id
            || $destino['tipo_kardex'] !== $mov->tipo_kardex
            || abs($destino['cantidad'] - (float) $mov->cantidad) > 0.0001
            || $fechaNueva !== Carbon::parse($mov->fecha_movimiento)->toDateString();

        $costos = array_intersect_key($nuevos, array_flip(['costo_unitario', 'costo_total']));
        $cambiaCosto = collect($costos)->contains(fn($v, $k) => abs((float) $v - (float) $mov->{$k}) > 0.000001);

        if (!$afectaStock && !$cambiaCosto) {
            return $mov; // nada que afecte al movimiento: queda tal cual
        }

        // El periodo de origen y el de destino deben estar abiertos
        self::verificarKardexAbierto((int) $mov->producto_id, $mov->tipo_kardex, $mov->fecha_movimiento);
        if ($afectaStock) {
            self::verificarKardexAbierto($destino['producto_id'], $destino['tipo_kardex'], $fechaNueva);
        }

        return DB::transaction(function () use ($mov, $destino, $costos, $afectaStock) {
            if ($afectaStock) {
                $signo = $mov->direccion === 'entrada' ? 1 : -1;

                // Quitar el efecto anterior (si era del año vigente)...
                if (DateHelper::esPeriodoVigente($mov->fecha_movimiento)) {
                    $this->ajustarStock((int) $mov->producto_id, (int) $mov->almacen_id, $mov->tipo_kardex, -$signo * (float) $mov->cantidad);
                }
                // ...y aplicar el nuevo (si es del año vigente)
                if (DateHelper::esPeriodoVigente($destino['fecha_movimiento'])) {
                    $this->ajustarStock($destino['producto_id'], $destino['almacen_id'], $destino['tipo_kardex'], $signo * $destino['cantidad']);
                }
            }

            // Mismo id: el kardex lo detecta como modificado (updated_at) y queda desactualizado
            $mov->update(array_merge($afectaStock ? $destino : [], $costos));

            return $mov;
        });
    }

    /**
     * Marca el movimiento de un origen como modificado sin cambiar cantidades (p. ej. cambió el
     * campo o la maquinaria de una salida, que el kardex muestra), para que su kardex se regenere.
     */
    public function marcarMovimientoModificado(string $origenType, int $origenId): void
    {
        $mov = MovimientoStock::where('origen_type', $origenType)->where('origen_id', $origenId)->first();
        if ($mov) {
            self::verificarKardexAbierto((int) $mov->producto_id, $mov->tipo_kardex, $mov->fecha_movimiento);
            $mov->touch();
        }
    }

    private function ajustarStock(int $productoId, int $almacenId, string $tipoKardex, float $delta): void
    {
        $stock = StockProducto::firstOrCreate(
            ['producto_id' => $productoId, 'almacen_id' => $almacenId, 'tipo_kardex' => $tipoKardex],
            ['cantidad' => 0]
        );
        StockProducto::where('id', $stock->id)->lockForUpdate()->increment('cantidad', $delta);
    }

    /**
     * Traslado entre almacenes: saca del almacén de origen y
     * aumenta en el almacén de destino (2 movimientos: salida + entrada).
     */
    public function transferir(
        int $productoId,
        int $almacenOrigenId,
        int $almacenDestinoId,
        float $cantidad,
        string $fechaTransferencia,
        string $tipoKardex
    ): TransferenciaAlmacen {
        return DB::transaction(function () use ($productoId, $almacenOrigenId, $almacenDestinoId, $cantidad, $fechaTransferencia, $tipoKardex) {

            $disponible = self::disponible($productoId, $almacenOrigenId, $tipoKardex);
            if ($cantidad > $disponible) {
                throw new \RuntimeException(
                    "Stock insuficiente en el almacén de origen. Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }

            $transferencia = TransferenciaAlmacen::create([
                'producto_id' => $productoId,
                'almacen_origen_id' => $almacenOrigenId,
                'almacen_destino_id' => $almacenDestinoId,
                'cantidad' => $cantidad,
                'fecha_transferencia' => $fechaTransferencia,
            ]);

            $this->registrarMovimiento('salida', $productoId, $almacenOrigenId, $cantidad, $fechaTransferencia, $tipoKardex, TransferenciaAlmacen::class, $transferencia->id);
            $this->registrarMovimiento('entrada', $productoId, $almacenDestinoId, $cantidad, $fechaTransferencia, $tipoKardex, TransferenciaAlmacen::class, $transferencia->id);

            return $transferencia;
        });
    }

    /**
     * Recalibra el stock vigente desde el kardex: stocks_productos = stock_final del kardex.
     * Solo aplica al kardex del año vigente; los de años anteriores no tocan el stock actual
     * (su stock_final sirve como saldo inicial del año siguiente al cerrar).
     * Se asume un solo almacén (el principal): el kardex no distingue almacenes.
     *
     * @return float|null el stock recalibrado, o null si no aplica (año no vigente)
     */
    public static function recalibrarDesdeKardex(InsKardex $kardex): ?float
    {
        if ((int) $kardex->anio !== (int) date('Y') || $kardex->stock_final === null) {
            return null;
        }

        $almacenId = AlmacenPrincipalServicio::obtenerAlmacenPrincipal()->id;
        $stockFinal = round((float) $kardex->stock_final, 4);

        return DB::transaction(function () use ($kardex, $almacenId, $stockFinal) {
            $stock = StockProducto::firstOrCreate(
                ['producto_id' => $kardex->producto_id, 'almacen_id' => $almacenId, 'tipo_kardex' => $kardex->tipo],
                ['cantidad' => 0]
            );
            StockProducto::where('id', $stock->id)->lockForUpdate()->update(['cantidad' => $stockFinal]);

            return $stockFinal;
        });
    }

    /** Saldo cacheado — rápido, sin sumar historial completo */
    public static function disponible(int $productoId, int $almacenId, string $tipoKardex): float
    {
        return (float) (StockProducto::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('tipo_kardex', $tipoKardex)
            ->value('cantidad') ?? 0);
    }

    /**
     * Saldo REAL, recalculado desde movimientos_stock.
     * Se usa solo para arqueo/auditoría.
     */
    public static function recalculadoDesdeMovimientos(int $productoId, int $almacenId, string $tipoKardex): float
    {
        $entradas = (float) MovimientoStock::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('tipo_kardex', $tipoKardex)
            ->where('direccion', 'entrada')->sum('cantidad');

        $salidas = (float) MovimientoStock::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('tipo_kardex', $tipoKardex)
            ->where('direccion', 'salida')->sum('cantidad');

        return $entradas - $salidas;
    }
    public static function obtenerStockPorTipo(int $productoId, ?int $almacenId = null): array
    {
        $almacenId = AlmacenPrincipalServicio::obtenerAlmacenPrincipal()->id;

        $filas = StockProducto::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->pluck('cantidad', 'tipo_kardex');

        return [
            'blanco' => (float) ($filas['blanco'] ?? 0),
            'negro' => (float) ($filas['negro'] ?? 0),
        ];
    }

    /**
     * Arqueo: compara el saldo cacheado contra el recalculado.
     */
    public static function auditar(int $productoId, int $almacenId, string $tipoKardex): array
    {
        $cacheado = self::disponible($productoId, $almacenId, $tipoKardex);
        $real = self::recalculadoDesdeMovimientos($productoId, $almacenId, $tipoKardex);

        return [
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'tipo_kardex' => $tipoKardex,
            'cacheado' => $cacheado,
            'calculado' => $real,
            'coincide' => abs($cacheado - $real) < 0.0001, // tolerancia por decimales flotantes
            'diferencia' => $cacheado - $real,
        ];
    }

    public function revertirMovimientosDeOrigen(
        string $origenType,
        int $origenId,
        ?int $almacenId = null,
        ?int $productoId = null
    ): void {
        DB::transaction(function () use ($origenType, $origenId, $almacenId, $productoId) {
            $movimientos = MovimientoStock::where('origen_type', $origenType)
                ->where('origen_id', $origenId)
                ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
                ->when($productoId, fn($q) => $q->where('producto_id', $productoId))
                ->get();

            //dd( $movimientos);
            foreach ($movimientos as $mov) {
              
                // Solo un kardex CERRADO bloquea. Si está activo, se elimina el movimiento; sus
                // movimientos en el kardex se borran en cascada (FK) y el kardex queda desactualizado.
                self::verificarKardexAbierto((int) $mov->producto_id, $mov->tipo_kardex, $mov->fecha_movimiento);

                if (DateHelper::esPeriodoVigente($mov->fecha_movimiento)) {
                    $stock = StockProducto::firstOrCreate(
                        ['producto_id' => $mov->producto_id, 'almacen_id' => $mov->almacen_id, 'tipo_kardex' => $mov->tipo_kardex],
                        ['cantidad' => 0]
                    );
                    $stock = StockProducto::where('id', $stock->id)->lockForUpdate()->first();

                    $delta = $mov->direccion === 'entrada' ? -$mov->cantidad : $mov->cantidad;
                    $stock->increment('cantidad', $delta);
                }

                $mov->delete();
            }
        });
    }
}