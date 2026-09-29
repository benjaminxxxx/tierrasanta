<?php

namespace App\Services\Insumo;

use App\Models\CompraProducto;
use App\Services\AuditoriaServicio;
use App\Services\ProductoServicio;
use Auth;
use DB;
use Exception;

class CompraInsumoServicio
{
    /**
     * Campos que definen si una fila está "vacía" → candidata a eliminación.
     */
    private static array $camposVacioCheck = [
        'fecha_compra',
        'producto_id',
        'tienda_comercial_id',
        'tipo_compra_codigo',
        'serie',
        'numero',
        'tipo_kardex',
        'total',
        'stock',
    ];
    private const CAMPOS_IGNORADOS_AUDITORIA = [
    'creado_por',
    'editado_por',
    'eliminado_por',
    'created_at',
    'updated_at',
    'deleted_at',
];

    /**
     * Etiquetas legibles para mensajes de error.
     */
    private static array $etiquetas = [
        'fecha_compra' => 'Fecha de compra',
        'producto_id' => 'Producto',
        'stock' => 'Stock',
        'total' => 'Total',
        'costo_por_kg' => 'Costo por kg',
        'tipo_compra_codigo' => 'Tipo de compra',
    ];

    /**
     * Campos requeridos para que una fila sea válida.
     */
    private static array $camposRequeridos = [
        'fecha_compra',
        'producto_id',
        'stock',
        'total',
        'tipo_kardex',
        //'costo_por_kg',
        'tipo_compra_codigo',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRADA PRINCIPAL (grid masivo)
    // ─────────────────────────────────────────────────────────────────────────

   public static function guardarCompras(array $filas): array
{
    $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

    DB::transaction(function () use ($filas, &$resultados) {
        foreach ($filas as $fila) {
            $id = $fila['id'] ?? null;

            // ── DETECCIÓN DE FILA VACÍA → ELIMINAR ───────────────────
            $filaVacia = collect(self::$camposVacioCheck)
                ->every(fn($campo) => is_null($fila[$campo] ?? null)
                    || ($fila[$campo] ?? '') === '');

            if ($filaVacia) {
                if ($id) {
                    $compra = CompraProducto::findOrFail($id);

                    AuditoriaServicio::registrar(
                        modelo: CompraProducto::class,
                        modeloId: $compra->id,
                        accion: 'eliminar',
                        antes: $compra->toArray(),
                        camposIgnorados: self::CAMPOS_IGNORADOS_AUDITORIA,
                    );

                    self::limpiarSalidasAsociadas($compra, $fila['tipo_kardex'] ?? $compra->tipo_kardex);
                    $compra->delete();
                    $resultados['eliminados']++;
                }
                continue;
            }

            // ── VALIDACIÓN DE CAMPOS REQUERIDOS ──────────────────────
            foreach (self::$camposRequeridos as $campo) {
                if (is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '') {
                    $etiqueta = self::$etiquetas[$campo] ?? $campo;
                    throw new Exception(
                        "El campo \"{$etiqueta}\" es obligatorio."
                        . ($id ? " (ID: {$id})" : '')
                    );
                }
            }

            $stock = (float) $fila['stock'];
            $total = (float) $fila['total'];

            if ($stock <= 0) {
                throw new Exception(
                    "El stock debe ser mayor a 0."
                    . ($id ? " (ID: {$id})" : '')
                );
            }

            $datos = self::prepararDatos($fila, $stock, $total);

            if ($id) {
                // ── ACTUALIZAR ────────────────────────────────────────
                $compra  = CompraProducto::findOrFail($id);
                $antes   = $compra->toArray();

                ProductoServicio::actualizarCompra($compra, $datos);

                AuditoriaServicio::registrar(
                    modelo: CompraProducto::class,
                    modeloId: $compra->id,
                    accion: 'editar',
                    antes: $antes,
                    despues: $compra->fresh()->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS_AUDITORIA,
                );

                $resultados['actualizados']++;
            } else {
                // ── CREAR ─────────────────────────────────────────────
                // ✅ creado_por solo al crear
                $datos['creado_por'] = Auth::id();

                $compra = CompraProducto::create($datos);

                AuditoriaServicio::registrar(
                    modelo: CompraProducto::class,
                    modeloId: $compra->id,
                    accion: 'crear',
                    despues: $compra->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS_AUDITORIA,
                );

                $resultados['creados']++;
            }
        }
    });

    return $resultados;
}

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    private static function prepararDatos(array $fila, float $stock, float $total): array
    {
        $tiendaId = isset($fila['tienda_comercial_id'])
            && (int) $fila['tienda_comercial_id'] !== 0
            ? $fila['tienda_comercial_id']
            : null;

        return [
            'producto_id' => $fila['producto_id'],
            'tienda_comercial_id' => $tiendaId,
            'fecha_compra' => $fila['fecha_compra'],
            'orden_compra' => $fila['orden_compra'] ?? null,
            'costo_por_kg' => $total / $stock,
            'total' => $total,
            'stock' => $stock,
            'fecha_termino' => $fila['fecha_termino'] ?? null,
            'estado' => $fila['estado'] ?? null,
            'tipo_compra_codigo' => $fila['tipo_compra_codigo'],
            'serie' => isset($fila['serie']) ? mb_strtoupper($fila['serie']) : null,
            'numero' => $fila['numero'] ?? null,
            'tabla12_tipo_operacion' => 2,
            'tipo_kardex' => $fila['tipo_kardex'] ?? null,
            // ✅ creado_por NO va aquí — se maneja por separado según contexto
            'editado_por' => null,
            'eliminado_por' => null,
        ];
    }

    /**
     * Replica la lógica de ProductoServicio::actualizarCompra para limpiar
     * salidas asociadas al kardex antes de eliminar una compra.
     */
    public static function limpiarSalidasAsociadas(CompraProducto $compra, ?string $tipoKardex): void
    {
        $salidaStocks = $compra->almacenSalida;
        if (!$salidaStocks)
            return;

        foreach ($salidaStocks as $salidaStock) {
            $salidaAlmacen = $salidaStock->salida;
            if ($salidaAlmacen) {
                $salidaAlmacen->update([
                    'tipo_kardex' => $tipoKardex,
                    //'costo_por_kg' => null,
                    'total_costo' => null,
                ]);
                $salidaAlmacen->compraStock()->delete();
            }
        }
    }
}