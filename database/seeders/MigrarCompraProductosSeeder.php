<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Presentacion;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrarCompraProductosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Almacén Central
            $almacen = Almacen::firstOrCreate(
                ['codigo' => 'ALM-01'],
                [
                    'nombre'       => 'Almacén Principal',
                    'es_principal' => true,
                    'activo'       => true,
                ]
            );

            // 2. Unidad base SUNAT (Ejemplo: ID/Código de UNIDADES '07')
            $unidadMedidaCodigo = DB::table('sunat_tabla6_codigo_unidad_medida')
    ->where('codigo', '07')
    ->orWhere('alias', 'UND')
    ->value('codigo') ?? '07';

            $proveedorDefectoId = Proveedor::first()?->id;
            $registrosAntiguos = DB::table('compra_productos')->get();

            // 3. Agrupar compras por Serie + Número o por Criterio de Fecha
            $comprasAgrupadas = $registrosAntiguos->groupBy(function ($item) {
                $serie = trim($item->serie ?? '');
                $numero = trim($item->numero ?? $item->orden_compra ?? '');

                if (empty($serie) && empty($numero)) {
                    return "SIN_NUMERO_{$item->fecha_compra}_{$item->tienda_comercial_id}_{$item->tipo_kardex}";
                }

                return "COMPRA_{$serie}_{$numero}_{$item->fecha_compra}";
            });

            // 4. Crear Cabeceras y Detalles
            foreach ($comprasAgrupadas as $claveGrupo => $items) {
                $primerItem = $items->first();

                $proveedorId = null;
                if ($primerItem->tienda_comercial_id) {
                    $proveedor = Proveedor::where('persona_id', $primerItem->tienda_comercial_id)->first()
                        ?? Proveedor::where('id', $primerItem->tienda_comercial_id)->first();

                    $proveedorId = $proveedor?->id;
                }

                if (!$proveedorId) {
                    $proveedorId = $proveedorDefectoId;
                }

                $totalCompra = $items->sum('total');
                $subtotalCompra = round($totalCompra / 1.18, 4);
                $igvCompra = round($totalCompra - $subtotalCompra, 4);

                $compra = Compra::create([
                    'proveedor_id'            => $proveedorId,
                    'almacen_id'              => $almacen->id,
                    'moneda'                  => 'PEN',
                    'tipo_cambio'             => 1.0000,
                    'tipo_comprobante_codigo' => $primerItem->tipo_compra_codigo,
                    'serie'                   => $primerItem->serie,
                    'numero'                  => $primerItem->numero ?? $primerItem->orden_compra,
                    'fecha_emision'           => $primerItem->fecha_compra,
                    'fecha_vencimiento'       => $primerItem->fecha_termino,
                    'forma_pago'              => 'contado',
                    'tipo_kardex'             => $primerItem->tipo_kardex ?? 'blanco',
                    'tabla12_tipo_operacion'  => $primerItem->tabla12_tipo_operacion,
                    'subtotal_neto'           => $subtotalCompra,
                    'igv_total'               => $igvCompra,
                    'total'                   => $totalCompra,
                    'notas'                   => 'Migrado desde compra_productos',
                    'creado_por'              => $primerItem->creado_por,
                    'editado_por'             => $primerItem->editado_por,
                    'eliminado_por'           => $primerItem->eliminado_por,
                    'created_at'              => $primerItem->created_at ?? now(),
                    'updated_at'              => $primerItem->updated_at ?? now(),
                    'deleted_at'              => $primerItem->deleted_at,
                ]);

                foreach ($items as $item) {
                    // Garantizar o crear la presentación base "Unidad" para este producto específico
                    $presentacion = Presentacion::firstOrCreate(
                        [
                            'producto_id' => $item->producto_id,
                            'nombre'      => 'Unidad',
                        ],
                        [
                            'unidad_medida_codigo'  => $unidadMedidaCodigo,
                            'factor_conversion' => 1.0000,
                            'es_compra_defecto' => true,
                            'activo'            => true,
                        ]
                    );

                    $cantidad = $item->stock > 0 ? $item->stock : 1;
                    $costoUnitario = $cantidad > 0 ? round($item->total / $cantidad, 4) : $item->total;

                    CompraDetalle::create([
                        'compra_id'                => $compra->id,
                        'producto_id'              => $item->producto_id,
                        'presentacion_id'          => $presentacion->id,
                        'nombre_presentacion'      => $presentacion->nombre,
                        'cantidad'                 => $cantidad,
                        'costo_unitario'           => $costoUnitario,
                        'porcentaje_descuento'     => 0,
                        'porcentaje_igv'           => 18.00,
                        'total_linea'              => $item->total,
                        
                        // Congelamiento de conversión para el Kardex
                        'factor_conversion_usado'  => $presentacion->factor_conversion,
                        'cantidad_base'            => $cantidad * $presentacion->factor_conversion,
                        'costo_unitario_base'      => $costoUnitario / $presentacion->factor_conversion,
                        
                        'created_at'               => $item->created_at ?? now(),
                        'updated_at'               => $item->updated_at ?? now(),
                    ]);
                }
            }
        });
    }
}