<?php

namespace App\Services\Almacen\Insumos;

use App\Constants\Permisos;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Excel de insumos (motor común SistemaLibroExcel): categorías, subcategorías, usos, proveedores, productos,
 * presentaciones y nutrientes por producto. Nunca borra: Activo = NO da de baja (productos, proveedores, subcategorías).
 */
class AlmacenInsumosDefinicion extends SistemaLibroExcelDefinicion
{
    public function reglas(): string
    {
        return AlmacenInsumosReglas::class;
    }

    public function permiso(): string
    {
        return Permisos::INSUMO_PRODUCTO_GESTIONAR;
    }

    public function leeme(): array
    {
        return [
            'Productos: se reconocen por nombre comercial + ingrediente activo. Cambiar el nombre crea otro producto; para retirarlo usa Activo = NO.',
            'Productos: la Descripción se arma sola ("NOMBRE (INGREDIENTE)"); es la que se usa en Presentaciones, Nutrientes por producto y en el Excel de compras.',
            'Unidad y tipo de existencia: códigos SUNAT (07 = UNIDADES, 01 = KILOGRAMOS, 08 = LITROS…; existencia 05 = SUMINISTROS DIVERSOS…).',
            'Nutrientes por producto: el nutriente debe existir en el Excel de recursos (hoja Nutrientes).',
            'Proveedores: se reconocen por RUC.',
        ];
    }

    // ------------------------------------------------------------------ filas del sistema

    public function filasSistema(): array
    {
        $usos = DB::table('ins_producto_usos as pu')->join('ins_usos as u', 'u.id', '=', 'pu.uso_id')
            ->orderBy('u.nombre')->get(['pu.producto_id', 'u.nombre'])->groupBy('producto_id');
        $subcategorias = DB::table('ins_subcategorias')->pluck('nombre', 'id');
        $productos = $this->productos();

        return [
            'categorias' => DB::table('ins_categorias')->orderBy('codigo')->get()->map(fn($c) => [
                'codigo' => $c->codigo, 'descripcion' => $c->descripcion, 'grupo_operativo' => $c->grupo_operativo,
                'definicion' => $c->definicion, 'criterio_uso' => $c->criterio_uso,
            ])->all(),
            'subcategorias' => DB::table('ins_subcategorias')->orderBy('categoria_codigo')->orderBy('nombre')->get()->map(fn($s) => [
                'categoria' => $s->categoria_codigo, 'nombre' => $s->nombre, 'descripcion' => $s->descripcion,
                'activo' => AlmacenInsumosReglas::sn(!$s->deleted_at),
            ])->all(),
            'usos' => DB::table('ins_usos')->orderBy('nombre')->get()->map(fn($u) => [
                'nombre' => $u->nombre, 'categoria' => $u->categoria_codigo, 'descripcion' => $u->descripcion,
            ])->all(),
            'proveedores' => DB::table('proveedores as v')->join('personas as p', 'p.id', '=', 'v.persona_id')
                ->orderBy('p.razon_social')->get(['p.numero_documento', 'p.razon_social', 'p.nombre_mostrar', 'p.direccion', 'p.telefono',
                    'p.telefono_movil', 'p.email', 'v.es_agente_retencion', 'v.deleted_at'])
                ->map(fn($v) => [
                    'ruc' => (string) $v->numero_documento, 'razon_social' => $v->razon_social ?: $v->nombre_mostrar, 'direccion' => $v->direccion,
                    'telefono' => $v->telefono_movil ?: $v->telefono, 'email' => $v->email,
                    'es_agente_retencion' => AlmacenInsumosReglas::sn($v->es_agente_retencion), 'activo' => AlmacenInsumosReglas::sn(!$v->deleted_at),
                ])->all(),
            'productos' => $productos->map(fn($p) => [
                'nombre' => $p->nombre_comercial, 'ingrediente' => $p->ingrediente_activo,
                'descripcion' => AlmacenInsumosReglas::descripcion($p->nombre_comercial, $p->ingrediente_activo),
                'categoria' => $p->categoria_codigo, 'subcategoria' => $subcategorias[$p->subcategoria_id] ?? null,
                'unidad' => $p->codigo_unidad_medida, 'existencia' => $p->codigo_tipo_existencia,
                'usos' => ($usos[$p->id] ?? collect())->pluck('nombre')->implode(', ') ?: null, 'activo' => AlmacenInsumosReglas::sn(!$p->deleted_at),
            ])->values()->all(),
            'presentaciones' => DB::table('presentaciones')->whereIn('producto_id', $productos->keys())->orderBy('producto_id')->orderBy('nombre')->get()
                ->map(fn($r) => [
                    'producto' => $this->descripcionDe($productos[$r->producto_id]), 'nombre' => $r->nombre, 'unidad' => $r->unidad_medida_codigo,
                    'factor' => $r->factor_conversion, 'compra_defecto' => AlmacenInsumosReglas::sn($r->es_compra_defecto), 'activo' => AlmacenInsumosReglas::sn($r->activo),
                ])->all(),
            'nutrientes' => DB::table('producto_nutrientes')->whereIn('producto_id', $productos->keys())->orderBy('producto_id')->orderBy('nutriente_codigo')->get()
                ->map(fn($r) => [
                    'producto' => $this->descripcionDe($productos[$r->producto_id]), 'nutriente' => $r->nutriente_codigo, 'porcentaje' => $r->porcentaje,
                ])->all(),
        ];
    }

    /**
     * Productos (también los dados de baja), uno por nombre + ingrediente: si en el sistema hay repetidos se toma el
     * primero activo (los demás quedan fuera del Excel; conviene unificarlos).
     */
    private function productos(): \Illuminate\Support\Collection
    {
        return DB::table('productos')->orderByRaw('deleted_at IS NOT NULL')->orderBy('id')->get()
            ->unique(fn($p) => mb_strtolower(trim($p->nombre_comercial)) . '|' . mb_strtolower(trim((string) $p->ingrediente_activo)))
            ->sortBy(fn($p) => mb_strtolower($p->nombre_comercial))
            ->keyBy('id');
    }

    private function descripcionDe(object $p): ?string
    {
        return AlmacenInsumosReglas::descripcion($p->nombre_comercial, $p->ingrediente_activo);
    }

    /** id del producto por nombre + ingrediente (activo primero). */
    private function productoId(?string $nombre, ?string $ingrediente): ?int
    {
        return DB::table('productos')->whereRaw('LOWER(TRIM(nombre_comercial)) = ?', [mb_strtolower(trim((string) $nombre))])
            ->whereRaw("LOWER(TRIM(COALESCE(ingrediente_activo, ''))) = ?", [mb_strtolower(trim((string) $ingrediente))])
            ->orderByRaw('deleted_at IS NOT NULL')->orderBy('id')->value('id');
    }

    /** id del producto por su descripción "NOMBRE (INGREDIENTE)". */
    private function productoPorDescripcion(string $descripcion): ?int
    {
        foreach ($this->productos() as $p) {
            if (mb_strtolower((string) $this->descripcionDe($p)) === mb_strtolower($descripcion)) {
                return $p->id;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------ análisis

    public function prepararFila(string $hoja, array $f): array
    {
        if ($hoja === 'productos') {
            $f['descripcion'] = AlmacenInsumosReglas::descripcion($f['nombre'], $f['ingrediente']);
            $usos = self::lista($f['usos']);
            sort($usos);
            $f['usos'] = $usos ? implode(', ', $usos) : null;
        }
        if ($hoja === 'proveedores' && $f['ruc'] !== null) {
            $f['ruc'] = preg_replace('/\D/', '', $f['ruc']);
        }
        if (in_array($hoja, ['productos', 'presentaciones'], true) && $f['unidad'] !== null && is_numeric($f['unidad'])) {
            $f['unidad'] = str_pad($f['unidad'], 2, '0', STR_PAD_LEFT);
        }
        if ($hoja === 'productos' && $f['existencia'] !== null && is_numeric($f['existencia'])) {
            $f['existencia'] = str_pad($f['existencia'], 2, '0', STR_PAD_LEFT);
        }
        return $f;
    }

    public function reglasFila(string $hoja, array $f, array &$avisos, array $contexto): array
    {
        $e = [];
        if ($hoja === 'proveedores' && $f['ruc'] !== null && !preg_match('/^\d{11}$/', $f['ruc'])) {
            $e[] = 'El RUC debe tener 11 dígitos.';
        }
        if ($hoja === 'proveedores' && $f['email'] !== null && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $e[] = "Correo \"{$f['email']}\" no es válido.";
        }
        if (in_array($hoja, ['productos', 'presentaciones'], true) && $f['unidad'] !== null
            && !DB::table('sunat_tabla6_codigo_unidad_medida')->where('codigo', $f['unidad'])->exists()) {
            $e[] = "Unidad \"{$f['unidad']}\" no es un código SUNAT (07 = UNIDADES, 01 = KILOGRAMOS, 08 = LITROS…).";
        }
        if ($hoja === 'productos' && $f['existencia'] !== null && !DB::table('sunat_tabla5_tipo_existencias')->where('codigo', $f['existencia'])->exists()) {
            $e[] = "Tipo de existencia \"{$f['existencia']}\" no es un código SUNAT.";
        }
        if ($hoja === 'productos' && $f['subcategoria'] !== null && $f['categoria'] !== null) {
            $enExcel = collect($contexto['analisis']['subcategorias']['filas'] ?? [])
                ->contains(fn($s) => $s['estado'] !== 'error' && mb_strtolower($s['datos']['nombre']) === mb_strtolower($f['subcategoria'])
                    && mb_strtolower($s['datos']['categoria']) === mb_strtolower($f['categoria']));
            if (!$enExcel && !DB::table('ins_subcategorias')->where('categoria_codigo', $f['categoria'])->whereRaw('LOWER(nombre) = ?', [mb_strtolower($f['subcategoria'])])->exists()) {
                $e[] = "Subcategoría \"{$f['subcategoria']}\" no existe en la categoría {$f['categoria']}.";
            }
        }
        if ($hoja === 'productos' && $f['usos'] !== null) {
            $enExcel = collect($contexto['analisis']['usos']['filas'] ?? [])->where('estado', '!=', 'error')->pluck('datos.nombre')->map(fn($u) => mb_strtolower($u))->all();
            foreach (self::lista($f['usos']) as $uso) {
                if (!in_array(mb_strtolower($uso), $enExcel, true) && !DB::table('ins_usos')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($uso)])->exists()) {
                    $e[] = "Uso \"{$uso}\" no existe (agrégalo en la hoja Usos).";
                }
            }
        }
        if ($hoja === 'nutrientes' && $f['nutriente'] !== null && !DB::table('nutrientes')->where('codigo', $f['nutriente'])->exists()) {
            $e[] = "Nutriente \"{$f['nutriente']}\" no existe: cárgalo primero en el Excel de recursos (hoja Nutrientes).";
        }
        if ($hoja === 'nutrientes' && is_numeric($f['porcentaje']) && ((float) $f['porcentaje'] < 0 || (float) $f['porcentaje'] > 100)) {
            $e[] = 'El % debe estar entre 0 y 100.';
        }
        if ($hoja === 'presentaciones' && is_numeric($f['factor']) && (float) $f['factor'] <= 0) {
            $e[] = 'El factor de conversión debe ser mayor que 0.';
        }
        return $e;
    }

    // ------------------------------------------------------------------ guardado

    protected function guardarCategorias(array $d): void
    {
        $this->upsert('ins_categorias', ['codigo' => $d['codigo']], [
            'descripcion' => $d['descripcion'], 'grupo_operativo' => $d['grupo_operativo'], 'definicion' => $d['definicion'], 'criterio_uso' => $d['criterio_uso'],
        ]);
    }

    protected function guardarSubcategorias(array $d): void
    {
        $actual = DB::table('ins_subcategorias')->where('categoria_codigo', $d['categoria'])->whereRaw('LOWER(nombre) = ?', [mb_strtolower($d['nombre'])])->first();
        $valores = ['nombre' => $d['nombre'], 'descripcion' => $d['descripcion'], 'updated_at' => now()]
            + ($d['activo'] === 'SI' ? ['deleted_at' => null, 'eliminado_por' => null]
                : ((!$actual || !$actual->deleted_at) ? ['deleted_at' => now(), 'eliminado_por' => auth()->id()] : []));
        $actual ? DB::table('ins_subcategorias')->where('id', $actual->id)->update($valores + ['editado_por' => auth()->id()])
            : DB::table('ins_subcategorias')->insert($valores + ['categoria_codigo' => $d['categoria'], 'creado_por' => auth()->id(), 'created_at' => now()]);
    }

    protected function guardarUsos(array $d): void
    {
        $actual = DB::table('ins_usos')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($d['nombre'])])
            ->where(fn($q) => $d['categoria'] === null ? $q->whereNull('categoria_codigo') : $q->where('categoria_codigo', $d['categoria']))->first();
        $actual ? DB::table('ins_usos')->where('id', $actual->id)->update(['descripcion' => $d['descripcion'], 'editado_por' => auth()->id(), 'updated_at' => now()])
            : DB::table('ins_usos')->insert(['nombre' => $d['nombre'], 'categoria_codigo' => $d['categoria'], 'descripcion' => $d['descripcion'],
                'creado_por' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Proveedor = persona (RUC) + proveedor; se crea la persona si no existe. */
    protected function guardarProveedores(array $d): void
    {
        $persona = DB::table('personas')->where('numero_documento', $d['ruc'])->first();
        $datosPersona = ['razon_social' => $d['razon_social'], 'nombre_mostrar' => $d['razon_social'], 'nombre_legal' => $d['razon_social'],
            'direccion' => $d['direccion'], 'telefono' => $d['telefono'], 'email' => $d['email'], 'updated_at' => now()];
        if ($persona) {
            DB::table('personas')->where('id', $persona->id)->update($datosPersona);
            $personaId = $persona->id;
        } else {
            $personaId = DB::table('personas')->insertGetId($datosPersona + ['codigo' => 'PER-' . strtoupper(Str::random(6)), 'tipo' => 'empresa',
                'tipo_documento' => 'RUC', 'numero_documento' => $d['ruc'], 'activo' => true, 'created_at' => now()]);
        }
        $proveedor = DB::table('proveedores')->where('persona_id', $personaId)->first();
        $valores = ['es_agente_retencion' => $d['es_agente_retencion'] === 'SI', 'updated_at' => now()]
            + ($d['activo'] === 'SI' ? ['deleted_at' => null, 'eliminado_por' => null]
                : ((!$proveedor || !$proveedor->deleted_at) ? ['deleted_at' => now(), 'eliminado_por' => auth()->id()] : []));
        $proveedor ? DB::table('proveedores')->where('id', $proveedor->id)->update($valores + ['editado_por' => auth()->id()])
            : DB::table('proveedores')->insert($valores + ['persona_id' => $personaId, 'creado_por' => auth()->id(), 'created_at' => now()]);
    }

    protected function guardarProductos(array $d): void
    {
        $id = $this->productoId($d['nombre'], $d['ingrediente']);
        $actual = $id ? DB::table('productos')->where('id', $id)->first() : null;
        $subcategoria = $d['subcategoria'] ? DB::table('ins_subcategorias')->where('categoria_codigo', $d['categoria'])
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($d['subcategoria'])])->value('id') : null;
        $valores = ['nombre_comercial' => $d['nombre'], 'ingrediente_activo' => $d['ingrediente'], 'categoria_codigo' => $d['categoria'],
            'subcategoria_id' => $subcategoria, 'codigo_unidad_medida' => $d['unidad'], 'codigo_tipo_existencia' => $d['existencia'], 'updated_at' => now()]
            + ($d['activo'] === 'SI' ? ['deleted_at' => null, 'eliminado_por' => null]
                : ((!$actual || !$actual->deleted_at) ? ['deleted_at' => now(), 'eliminado_por' => auth()->id()] : []));
        if ($actual) {
            DB::table('productos')->where('id', $id)->update($valores + ['editado_por' => auth()->id()]);
        } else {
            $id = DB::table('productos')->insertGetId($valores + ['creado_por' => auth()->id(), 'created_at' => now()]);
        }
        // Usos del producto: los del Excel reemplazan a los del sistema
        $usos = collect(self::lista($d['usos']))->map(fn($u) => DB::table('ins_usos')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($u)])->value('id'))->filter()->unique();
        DB::table('ins_producto_usos')->where('producto_id', $id)->whereNotIn('uso_id', $usos->all())->delete();
        foreach ($usos as $usoId) {
            DB::table('ins_producto_usos')->insertOrIgnore(['producto_id' => $id, 'uso_id' => $usoId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function guardarPresentaciones(array $d): void
    {
        $productoId = $this->productoPorDescripcion($d['producto']);
        if (!$productoId) {
            return;
        }
        $actual = DB::table('presentaciones')->where('producto_id', $productoId)->whereRaw('LOWER(nombre) = ?', [mb_strtolower($d['nombre'])])->first();
        $valores = ['nombre' => $d['nombre'], 'unidad_medida_codigo' => $d['unidad'], 'factor_conversion' => (float) $d['factor'],
            'es_compra_defecto' => $d['compra_defecto'] === 'SI', 'activo' => $d['activo'] === 'SI', 'updated_at' => now()];
        if ($d['compra_defecto'] === 'SI') {
            DB::table('presentaciones')->where('producto_id', $productoId)->update(['es_compra_defecto' => false]); // una por producto
        }
        $actual ? DB::table('presentaciones')->where('id', $actual->id)->update($valores)
            : DB::table('presentaciones')->insert($valores + ['producto_id' => $productoId, 'created_at' => now()]);
    }

    protected function guardarNutrientes(array $d): void
    {
        $productoId = $this->productoPorDescripcion($d['producto']);
        if ($productoId) {
            $this->upsert('producto_nutrientes', ['producto_id' => $productoId, 'nutriente_codigo' => $d['nutriente']], ['porcentaje' => (float) $d['porcentaje']]);
        }
    }
}
