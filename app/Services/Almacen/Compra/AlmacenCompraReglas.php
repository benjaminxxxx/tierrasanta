<?php

namespace App\Services\Almacen\Compra;

/**
 * Reglas de compras y de su Excel (exportar un mes / importar compras nuevas):
 *
 * - Serie y número son opcionales (compras antiguas sin serie). Factura o boleta sin serie: solo aviso.
 * - Una compra se identifica por RUC del proveedor + tipo de comprobante + serie + número (sin serie: RUC + tipo +
 *   número). Al importar, las que ya existen se muestran como "ya registrada" y no se tocan.
 * - Hoja "Compras": una fila por producto; las filas con el mismo comprobante forman una compra.
 * - Hoja "Productos": todos los productos del sistema ordenados por descripción (sugerencia del Excel). Los productos
 *   nuevos se agregan ahí (nombre, categoría, unidad) y se crean al importar. Un producto que no está ni en el sistema
 *   ni en la hoja es un error de digitación: la compra se bloquea y se sugieren nombres parecidos.
 * - Hoja "Proveedores": RUC + razón social; los RUC nuevos se crean al importar.
 */
class AlmacenCompraReglas
{
    /** Columnas de la hoja Compras (clave => título). El orden es el del Excel. */
    public const COLUMNAS = [
        'fecha' => 'Fecha',
        'tipo' => 'Tipo comprobante',
        'serie' => 'Serie',
        'numero' => 'Número',
        'ruc' => 'RUC proveedor',
        'kardex' => 'Kardex (blanco/negro)',
        'almacen' => 'Almacén',
        'moneda' => 'Moneda',
        'tipo_cambio' => 'Tipo cambio',
        'forma_pago' => 'Forma pago',
        'fecha_vencimiento' => 'Fecha vencimiento',
        'producto' => 'Producto',
        'presentacion' => 'Presentación',
        'cantidad' => 'Cantidad',
        'precio' => 'Precio unit. (inc. IGV)',
        'descuento' => 'Desc. %',
        'igv' => 'IGV %',
        'subtotal' => 'Subtotal (inc. IGV)',
        'notas' => 'Notas',
    ];

    public const COLUMNAS_PRODUCTOS = ['Descripción', 'Nombre comercial', 'Ingrediente activo', 'Categoría', 'Unidad (SUNAT)', 'Tipo existencia'];
    public const COLUMNAS_PROVEEDORES = ['RUC', 'Razón social'];

    /** Comprobantes que siempre deberían tener serie (aviso, no bloquea). */
    public const TIPOS_CON_SERIE = ['01' => 'Factura', '03' => 'Boleta'];

    public const TIPO_EXISTENCIA_DEFECTO = '06';
    public const UNIDAD_DEFECTO = '01';

    /** Aviso si un comprobante debería tener serie y no la tiene; null si está bien. */
    public static function avisoSerie(?string $tipo, ?string $serie): ?string
    {
        $tipo = (string) $tipo;
        return isset(self::TIPOS_CON_SERIE[$tipo]) && trim((string) $serie) === ''
            ? 'Toda ' . mb_strtolower(self::TIPOS_CON_SERIE[$tipo]) . ' debería tener serie (se permite vacía por compras antiguas con datos perdidos).'
            : null;
    }

    /** Clave para detectar la misma compra: RUC|tipo|serie|número (serie vacía permitida). */
    public static function clave(?string $ruc, ?string $tipo, ?string $serie, ?string $numero): string
    {
        $limpiar = fn($v) => mb_strtoupper(preg_replace('/\s+/', '', (string) $v));
        $numero = ltrim($limpiar($numero), '0') ?: $limpiar($numero);
        return implode('|', [$limpiar($ruc), $limpiar($tipo), $limpiar($serie), $numero]);
    }

    /** Descripción normalizada para comparar productos (mayúsculas, sin tildes ni espacios dobles). */
    public static function normalizar(?string $texto): string
    {
        $t = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $texto)));
        return strtr($t, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);
    }

    /** Descripción de un producto en el Excel: nombre comercial (+ ingrediente activo). */
    public static function descripcion(?string $nombre, ?string $ingrediente): string
    {
        $nombre = trim((string) $nombre);
        $ingrediente = trim((string) $ingrediente);
        return $ingrediente !== '' ? "{$nombre} ({$ingrediente})" : $nombre;
    }
}
