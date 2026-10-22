<?php

namespace App\Services\Almacen\Insumos;

use App\Services\Sistema\LibroExcel\SistemaLibroExcelReglas;

/**
 * Excel de insumos: la estructura del almacén (categorías con su grupo operativo, subcategorías, usos), los proveedores
 * (por RUC) y los productos con sus presentaciones y nutrientes. Un producto se identifica por nombre comercial +
 * ingrediente activo (igual en cualquier sistema); su "Descripción" es la que se usa en compras y en las otras hojas.
 * Hojas en orden de dependencias: categorías → subcategorías / usos → proveedores → productos → presentaciones / nutrientes.
 */
class AlmacenInsumosReglas extends SistemaLibroExcelReglas
{
    public const PREFIJO_ARCHIVO = 'INSUMOS';
    public const TITULO = 'Excel de insumos';

    public const HOJAS = [
        'categorias' => ['Categorías', ['codigo'], [
            'codigo' => ['t' => 'Código', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto', 'req' => true],
            'grupo_operativo' => ['t' => 'Grupo operativo', 'tipo' => 'texto', 'req' => true, 'op' => 'grupo_operativo'],
            'definicion' => ['t' => 'Definición', 'tipo' => 'texto'],
            'criterio_uso' => ['t' => 'Criterio de uso', 'tipo' => 'texto'],
        ]],
        'subcategorias' => ['Subcategorías', ['categoria', 'nombre'], [
            'categoria' => ['t' => 'Categoría (código)', 'tipo' => 'texto', 'req' => true, 'ref' => 'categorias.codigo'],
            'nombre' => ['t' => 'Subcategoría', 'tipo' => 'texto', 'req' => true],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'usos' => ['Usos', ['nombre', 'categoria'], [
            'nombre' => ['t' => 'Uso', 'tipo' => 'texto', 'req' => true],
            'categoria' => ['t' => 'Categoría (código)', 'tipo' => 'texto', 'ref' => 'categorias.codigo'],
            'descripcion' => ['t' => 'Descripción', 'tipo' => 'texto'],
        ]],
        'proveedores' => ['Proveedores', ['ruc'], [
            'ruc' => ['t' => 'RUC', 'tipo' => 'texto', 'req' => true],
            'razon_social' => ['t' => 'Razón social', 'tipo' => 'texto', 'req' => true],
            'direccion' => ['t' => 'Dirección', 'tipo' => 'texto'],
            'telefono' => ['t' => 'Teléfono', 'tipo' => 'texto'],
            'email' => ['t' => 'Correo', 'tipo' => 'texto'],
            'es_agente_retencion' => ['t' => 'Agente de retención', 'tipo' => 'sino', 'def' => 'NO'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'productos' => ['Productos', ['nombre', 'ingrediente'], [
            'nombre' => ['t' => 'Nombre comercial', 'tipo' => 'texto', 'req' => true],
            'ingrediente' => ['t' => 'Ingrediente activo', 'tipo' => 'texto'],
            'descripcion' => ['t' => 'Descripción (se arma sola)', 'tipo' => 'texto', 'lectura' => true],
            'categoria' => ['t' => 'Categoría (código)', 'tipo' => 'texto', 'req' => true, 'ref' => 'categorias.codigo'],
            'subcategoria' => ['t' => 'Subcategoría', 'tipo' => 'texto'],
            'unidad' => ['t' => 'Unidad (código SUNAT)', 'tipo' => 'texto', 'req' => true],
            'existencia' => ['t' => 'Tipo de existencia (código SUNAT)', 'tipo' => 'texto'],
            'usos' => ['t' => 'Usos (separados por coma)', 'tipo' => 'texto'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'presentaciones' => ['Presentaciones', ['producto', 'nombre'], [
            'producto' => ['t' => 'Producto (descripción)', 'tipo' => 'texto', 'req' => true, 'ref' => 'productos.descripcion'],
            'nombre' => ['t' => 'Presentación', 'tipo' => 'texto', 'req' => true],
            'unidad' => ['t' => 'Unidad (código SUNAT)', 'tipo' => 'texto', 'req' => true],
            'factor' => ['t' => 'Factor de conversión', 'tipo' => 'decimal', 'req' => true],
            'compra_defecto' => ['t' => 'Por defecto en compras', 'tipo' => 'sino', 'def' => 'NO'],
            'activo' => ['t' => 'Activo', 'tipo' => 'sino', 'def' => 'SI'],
        ]],
        'nutrientes' => ['Nutrientes por producto', ['producto', 'nutriente'], [
            'producto' => ['t' => 'Producto (descripción)', 'tipo' => 'texto', 'req' => true, 'ref' => 'productos.descripcion'],
            'nutriente' => ['t' => 'Nutriente (código)', 'tipo' => 'texto', 'req' => true],
            'porcentaje' => ['t' => '%', 'tipo' => 'decimal', 'req' => true],
        ]],
    ];

    public const OPCIONES = [
        'grupo_operativo' => ['pesticida', 'fertilizante', 'combustible'],
    ];

    /** "NOMBRE (INGREDIENTE)" o solo el nombre: la descripción con la que se reconoce el producto (compras, kardex). */
    public static function descripcion(?string $nombre, ?string $ingrediente): ?string
    {
        $nombre = trim((string) $nombre);
        $ingrediente = trim((string) $ingrediente);
        if ($nombre === '') {
            return null;
        }
        return $ingrediente !== '' ? "{$nombre} ({$ingrediente})" : $nombre;
    }
}
