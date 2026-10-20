<?php

namespace App\Services\Almacen\Compra;

use Illuminate\Support\Facades\DB;

/**
 * Catálogos del Excel de compras: productos con descripción única (para sugerir y para reconocer al importar),
 * proveedores por RUC y listas (comprobantes, almacenes, categorías, unidades).
 */
class AlmacenCompraCatalogoConsulta
{
    /**
     * Productos ordenados por descripción. Si dos productos tienen la misma descripción se les agrega " #id" para que
     * cada fila del Excel apunte a uno solo.
     *
     * @return array<int, array{id:int, descripcion:string, nombre:string, ingrediente:?string, categoria:?string, unidad:?string, existencia:?string}>
     */
    public function productos(): array
    {
        $filas = DB::table('productos')->whereNull('deleted_at')
            ->get(['id', 'nombre_comercial', 'ingrediente_activo', 'categoria_codigo', 'codigo_unidad_medida', 'codigo_tipo_existencia']);
        $conteo = $filas->countBy(fn($p) => AlmacenCompraReglas::normalizar(AlmacenCompraReglas::descripcion($p->nombre_comercial, $p->ingrediente_activo)));
        return $filas->map(function ($p) use ($conteo) {
            $desc = AlmacenCompraReglas::descripcion($p->nombre_comercial, $p->ingrediente_activo);
            if ($conteo[AlmacenCompraReglas::normalizar($desc)] > 1) {
                $desc .= " #{$p->id}";
            }
            return [
                'id' => $p->id, 'descripcion' => $desc, 'nombre' => $p->nombre_comercial, 'ingrediente' => $p->ingrediente_activo,
                'categoria' => $p->categoria_codigo, 'unidad' => $p->codigo_unidad_medida, 'existencia' => $p->codigo_tipo_existencia,
            ];
        })->sortBy(fn($p) => AlmacenCompraReglas::normalizar($p['descripcion']))->values()->all();
    }

    /** @return array<string, int> descripción normalizada => producto_id */
    public function indiceProductos(): array
    {
        $indice = [];
        foreach ($this->productos() as $p) {
            $indice[AlmacenCompraReglas::normalizar($p['descripcion'])] = $p['id'];
        }
        return $indice;
    }

    /** @return array<int, array{id:int, ruc:string, razon_social:string}> */
    public function proveedores(): array
    {
        return DB::table('proveedores as v')->join('personas as p', 'p.id', '=', 'v.persona_id')
            ->whereNull('v.deleted_at')->orderBy('p.razon_social')
            ->get(['v.id', 'p.numero_documento', 'p.razon_social', 'p.nombre_mostrar'])
            ->map(fn($v) => ['id' => $v->id, 'ruc' => (string) $v->numero_documento, 'razon_social' => $v->razon_social ?: $v->nombre_mostrar])
            ->all();
    }

    public function tiposComprobante(): array
    {
        return DB::table('sunat_tabla10_tipo_comprobantes_pago')->orderBy('codigo')->pluck('descripcion', 'codigo')->all();
    }

    public function almacenes(): array
    {
        return DB::table('almacenes')->whereNull('deleted_at')->orderByDesc('es_principal')->orderBy('nombre')->pluck('nombre', 'id')->all();
    }

    public function categorias(): array
    {
        return DB::table('ins_categorias')->orderBy('codigo')->pluck('descripcion', 'codigo')->all();
    }

    public function unidades(): array
    {
        return DB::table('sunat_tabla6_codigo_unidad_medida')->orderBy('codigo')->pluck('descripcion', 'codigo')->all();
    }

    public function tiposExistencia(): array
    {
        return DB::table('sunat_tabla5_tipo_existencias')->orderBy('codigo')->pluck('descripcion', 'codigo')->all();
    }
}
