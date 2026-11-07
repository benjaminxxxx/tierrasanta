<?php

namespace App\Services\Almacen\Kardex;

use App\Models\InsKardex;

/**
 * Después de editar o eliminar algo que mueve el kardex (el kardex mismo, una compra, una salida) se pregunta si se
 * regenera de una vez o más adelante (queda en Tareas pendientes → Kardex por regenerar). Aquí se arma la lista de
 * kardex que quedaron desactualizados y se regeneran.
 */
class AlmacenKardexRegeneracionProceso
{
    public function __construct(
        private KardexActualizacionServicio $actualizacion,
        private InsumoKardexMovimientosServicio $movimientos,
    ) {
    }

    /**
     * Kardex desactualizados entre los indicados.
     *
     * @param array $claves [['producto_id' => , 'anio' => , 'tipo' => 'blanco'|'negro'|null], …] (tipo null = ambos)
     * @param array $kardexIds kardex indicados directamente
     * @return array [['id', 'producto', 'tipo', 'anio', 'motivo', 'cerrado'], …]
     */
    public function desactualizados(array $claves = [], array $kardexIds = []): array
    {
        $query = InsKardex::with('producto')->where(function ($q) use ($claves, $kardexIds) {
            $q->whereIn('id', $kardexIds ?: [0]);
            foreach ($claves as $c) {
                if (empty($c['producto_id']) || empty($c['anio'])) {
                    continue;
                }
                $q->orWhere(fn($w) => $w->where('producto_id', $c['producto_id'])->where('anio', $c['anio'])
                    ->when(!empty($c['tipo']), fn($t) => $t->where('tipo', $c['tipo'])));
            }
        });

        return $query->get()->map(function (InsKardex $k) {
            $motivo = $this->actualizacion->motivoDesactualizado($k);
            return $motivo ? [
                'id' => $k->id,
                'producto' => $k->producto?->nombre_comercial ?? "Producto #{$k->producto_id}",
                'tipo' => $k->tipo,
                'anio' => (int) $k->anio,
                'motivo' => $motivo,
                'cerrado' => $k->estado === 'cerrado',
            ] : null;
        })->filter()->values()->all();
    }

    /** @return array{regenerados: string[], errores: string[]} */
    public function regenerar(array $kardexIds): array
    {
        $regenerados = $errores = [];
        foreach (InsKardex::with('producto')->whereIn('id', $kardexIds)->get() as $k) {
            $nombre = ($k->producto?->nombre_comercial ?? "Producto #{$k->producto_id}") . " ({$k->tipo} {$k->anio})";
            if ($k->estado === 'cerrado') {
                $errores[] = "{$nombre}: está cerrado, reábrelo para regenerarlo.";
                continue;
            }
            try {
                $this->movimientos->generarMovimientos($k);
                $regenerados[] = $nombre;
            } catch (\Throwable $e) {
                $errores[] = "{$nombre}: {$e->getMessage()}";
            }
        }
        return ['regenerados' => $regenerados, 'errores' => $errores];
    }
}
