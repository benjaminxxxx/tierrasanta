<?php

namespace App\Services\Almacen\Kardex;

use App\Models\AlmacenProductoSalida;
use App\Models\InsKardex;
use App\Models\InsKardexMovimiento;
use App\Models\MovimientoStock;
use Exception;
use Illuminate\Support\Collection;

/**
 * Garantiza que el costo de las salidas (almacen_producto_salidas.total_costo) esté vigente
 * antes de usarlo en los costos por campo.
 *
 * El costo de una salida lo fija la generación de movimientos del kardex (promedio/PEPS). Si
 * después de esa fecha de corte se crean, editan o eliminan compras o salidas, los costos
 * quedan desactualizados: aquí se detecta y se intenta regenerar el kardex.
 */
class KardexActualizacionServicio
{
    public function __construct(private InsumoKardexMovimientosServicio $movimientosServicio)
    {
    }

    /**
     * Devuelve el motivo por el que el kardex está desactualizado, o null si está al día.
     */
    public function motivoDesactualizado(InsKardex $kardex): ?string
    {
        $corte = $kardex->movimientos_actualizados_at;
        if (!$corte) {
            return 'nunca se generaron sus movimientos';
        }

        // Cambios en el saldo inicial u otros datos del kardex después del corte
        if ($kardex->updated_at && $kardex->updated_at->gt($corte)) {
            return 'el kardex se modificó después de la fecha de corte';
        }

        $movimientosStock = MovimientoStock::where('producto_id', $kardex->producto_id)
            ->where('tipo_kardex', $kardex->tipo)
            ->whereBetween('fecha_movimiento', ["{$kardex->anio}-01-01", "{$kardex->anio}-12-31"])
            ->where('cantidad', '>', 0); // los de cantidad 0 no generan movimiento en el kardex

        // Compras/salidas editadas (se revierten y recrean) o nuevas después del corte.
        // >= porque los timestamps son al segundo: ante un empate se prefiere regenerar de más.
        if ((clone $movimientosStock)->where('updated_at', '>=', $corte)->exists()) {
            return 'hay compras o salidas registradas o modificadas después de la fecha de corte';
        }

        // Compras/salidas eliminadas (el movimiento de stock se borra, no deja fecha)
        $idsStock = (clone $movimientosStock)->pluck('id')->sort()->values()->all();
        $idsKardex = InsKardexMovimiento::where('kardex_id', $kardex->id)
            ->whereNotNull('stock_movimiento_id')
            ->pluck('stock_movimiento_id')->sort()->values()->all();

        if ($idsStock !== $idsKardex) {
            return 'hay compras o salidas agregadas o eliminadas que el kardex no procesó';
        }

        return null;
    }

    /**
     * Asegura que estén al día los kardex usados por las salidas dadas.
     * Regenera los desactualizados; si alguno no se puede actualizar, lanza una excepción
     * con todos los problemas encontrados (los que sí se regeneraron quedan guardados).
     *
     * @param Collection<AlmacenProductoSalida> $salidas
     * @return array{actualizados: string[], al_dia: int}
     */
    public function asegurarActualizados(Collection $salidas): array
    {
        $errores = [];
        $actualizados = [];
        $alDia = 0;

        $grupos = $salidas->groupBy(fn($s) => $s->producto_id . '|' . $s->tipo_kardex . '|' . $s->fecha_reporte->year);

        foreach ($grupos as $clave => $salidasGrupo) {
            [$productoId, $tipo, $anio] = explode('|', $clave);
            $nombre = $salidasGrupo->first()->producto?->nombre_completo ?? "producto #{$productoId}";
            $etiqueta = "{$nombre} (kardex {$tipo} {$anio})";

            $kardex = InsKardex::where('producto_id', $productoId)
                ->where('tipo', $tipo)
                ->where('anio', $anio)
                ->first();

            if (!$kardex) {
                $errores[] = "{$etiqueta}: no existe el kardex; créalo para poder costear sus salidas.";
                continue;
            }

            $motivo = $this->motivoDesactualizado($kardex);
            if ($motivo === null) {
                $alDia++;
                continue;
            }

            if ($kardex->estado === 'cerrado') {
                $errores[] = "{$etiqueta}: está cerrado pero {$motivo}. Revísalo antes de consolidar.";
                continue;
            }

            try {
                $this->movimientosServicio->generarMovimientos($kardex);
                $actualizados[] = $etiqueta;
            } catch (\Throwable $e) {
                $errores[] = "{$etiqueta}: no se pudo actualizar ({$motivo}). {$e->getMessage()}";
            }
        }

        // Una salida sin movimiento de stock nunca recibe costo del kardex.
        $sinMovimiento = AlmacenProductoSalida::whereIn('id', $salidas->pluck('id'))
            ->whereNull('movimiento_id')
            ->with('producto')
            ->get()
            ->groupBy(fn($s) => $s->producto?->nombre_completo ?? "producto #{$s->producto_id}");

        foreach ($sinMovimiento as $nombre => $lista) {
            $errores[] = "{$nombre}: {$lista->count()} salida(s) no tienen movimiento en el kardex (sin costo calculado).";
        }

        if ($errores) {
            $prefijo = $actualizados
                ? 'Se actualizaron ' . count($actualizados) . ' kardex, pero hay problemas: '
                : 'No se pudo asegurar el costo de los insumos: ';
            throw new Exception($prefijo . implode(' | ', $errores));
        }

        return ['actualizados' => $actualizados, 'al_dia' => $alDia];
    }
}
