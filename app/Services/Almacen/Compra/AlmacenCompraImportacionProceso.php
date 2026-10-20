<?php

namespace App\Services\Almacen\Compra;

use App\Models\Persona;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Almacen\CompraService;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa el Excel de compras ya revisado en la vista previa: vuelve a leerlo y analizarlo (no confía en lo que tenga
 * la pantalla), crea los proveedores y productos nuevos que usan las compras, y registra solo las compras "nuevas"
 * (sin errores y que no existían) con CompraService (mismo cálculo y movimientos de stock que el formulario).
 * Las compras con errores o ya registradas se omiten. El kardex de esos productos queda pendiente de regenerar.
 */
class AlmacenCompraImportacionProceso
{
    public function __construct(
        private AlmacenCompraExcel $excel,
        private AlmacenCompraImportacionConsulta $consulta,
        private CompraService $compras,
    ) {
    }

    /** @return array{compras:int, productos:int, proveedores:int, omitidas:int, total:float} */
    public function importar(string $ruta): array
    {
        $analisis = $this->consulta->analizar($this->excel->leer($ruta));
        $nuevas = collect($analisis['compras'])->where('estado', 'nueva');
        if ($nuevas->isEmpty()) {
            throw new \App\Exceptions\ErrorControlado('No hay compras nuevas sin errores para importar.');
        }

        return DB::transaction(function () use ($analisis, $nuevas) {
            $proveedores = [];
            foreach ($analisis['proveedores_nuevos'] as $p) {
                if (!$nuevas->contains(fn($c) => $c['ruc'] === $p['ruc'])) {
                    continue;
                }
                $persona = Persona::firstOrCreate(
                    ['tipo_documento' => 'RUC', 'numero_documento' => $p['ruc']],
                    ['codigo' => 'PER-' . strtoupper(Str::random(6)), 'tipo' => 'empresa', 'razon_social' => $p['razon_social'],
                        'nombre_mostrar' => $p['razon_social'], 'nombre_legal' => $p['razon_social'], 'activo' => true]
                );
                $proveedores[$p['ruc']] = Proveedor::firstOrCreate(['persona_id' => $persona->id], ['creado_por' => auth()->id()])->id;
            }

            $productos = [];
            foreach ($analisis['productos_nuevos'] as $p) {
                $clave = AlmacenCompraReglas::normalizar($p['descripcion']);
                $usado = $nuevas->contains(fn($c) => collect($c['lineas'])->contains('producto_nuevo', $clave));
                if (!$usado || $p['errores']) {
                    continue;
                }
                $productos[$clave] = Producto::create([
                    'nombre_comercial' => $p['nombre'],
                    'ingrediente_activo' => $p['ingrediente'],
                    'categoria_codigo' => $p['categoria'],
                    'codigo_unidad_medida' => $p['unidad'] ?: AlmacenCompraReglas::UNIDAD_DEFECTO,
                    'codigo_tipo_existencia' => $p['existencia'] ?: AlmacenCompraReglas::TIPO_EXISTENCIA_DEFECTO,
                    'creado_por' => auth()->id(),
                ])->id;
            }

            foreach ($nuevas as $c) {
                $cabecera = [
                    'proveedor_id' => $c['proveedor_id'] ?? $proveedores[$c['ruc']],
                    'almacen_id' => $c['almacen_id'],
                    'moneda' => $c['moneda'],
                    'tipo_cambio' => $c['moneda'] === 'USD' ? $c['tipo_cambio'] : 1,
                    'tipo_comprobante_codigo' => $c['tipo'],
                    'serie' => $c['serie'] ?: null,
                    'numero' => $c['numero'],
                    'fecha_emision' => $c['fecha'],
                    'fecha_vencimiento' => $c['fecha_vencimiento'],
                    'forma_pago' => $c['forma_pago'],
                    'notas' => trim(($c['notas'] ?? '') . ' [importada desde Excel]'),
                    'tipo_kardex' => $c['kardex'],
                ];
                $detalles = array_map(fn($l) => [
                    'producto_id' => $l['producto_id'] ?? $productos[$l['producto_nuevo']],
                    'presentacion_id' => null,
                    'nombre_presentacion' => $l['presentacion'],
                    'factor_conversion' => 1,
                    'cantidad' => $l['cantidad'],
                    'costo_unitario' => $l['precio'],
                    'porcentaje_descuento' => $l['descuento'],
                    'porcentaje_igv' => $l['igv'],
                    'total_linea' => $l['subtotal'],
                ], $c['lineas']);
                $this->compras->crear($cabecera, $detalles);
            }

            $resultado = [
                'compras' => $nuevas->count(),
                'productos' => count($productos),
                'proveedores' => count($proveedores),
                'omitidas' => count($analisis['compras']) - $nuevas->count(),
                'total' => round($nuevas->sum('total'), 2),
            ];
            AuditoriaServicio::registrar(
                modelo: \App\Models\Compra::class,
                modeloId: 0,
                accion: 'crear',
                despues: $resultado,
                observacion: "Importación de compras desde Excel: {$resultado['compras']} compras (S/ " . number_format($resultado['total'], 2)
                    . "), {$resultado['productos']} productos y {$resultado['proveedores']} proveedores nuevos.",
            );
            return $resultado;
        });
    }
}
