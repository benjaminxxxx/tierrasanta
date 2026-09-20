<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrarComprasAntiguasSeeder extends Seeder
{
    /** @var array<int,int> tienda_comercial_id (viejo) => proveedor_id (nuevo) */
    private array $mapaTiendaAProveedor = [];

    private ?int $proveedorGenericoId = null;

    public function run(): void
    {
        $this->migrarProveedores();
        $this->migrarCompras();
    }

    // ──────────────────────────────────────────────────────────
    // 1. tienda_comercials -> personas + proveedores
    // ──────────────────────────────────────────────────────────
    private function migrarProveedores(): void
    {
        $tiendas = DB::table('tienda_comercials')->get();

        foreach ($tiendas as $tienda) {
            $nombreClave = $this->nombreClaveTienda($tienda);

            // ¿Ya existe una persona con este razon_social? -> reusar, no duplicar.
            $personaExistente = Persona::where('razon_social', $nombreClave)->first();

            if ($personaExistente) {
                $proveedorExistente = Proveedor::where('persona_id', $personaExistente->id)->first();
                if ($proveedorExistente) {
                    $this->mapaTiendaAProveedor[$tienda->id] = $proveedorExistente->id;
                    continue;
                }
            }

            [$tipoDocumento, $numeroDocumento] = $this->resolverDocumento($tienda->ruc);

            $persona = $personaExistente ?? Persona::create([
                'codigo' => $this->generarCodigoPersona($nombreClave),
                'tipo' => 'empresa',
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'razon_social' => $nombreClave,
                'nombre_mostrar' => $nombreClave,
                'nombre_legal' => $tienda->razon_social ?: $nombreClave,
                'telefono' => $tienda->contacto,
                'departamento' => $tienda->departamento,
                'provincia' => $tienda->provincia,
                'distrito' => $tienda->distrito,
                'direccion' => $tienda->direccion_fiscal,
                'activo' => true,
            ]);

            $proveedor = Proveedor::create([
                'persona_id' => $persona->id,
                'tipo_contribuyente' => $tienda->tipo_contribuyente,
                'estado_contribuyente' => $tienda->estado_contribuyente,
                'estado_domicilio' => $tienda->estado_domicilio,
                'condicion' => $tienda->condicion,
                'fecha_inscripcion' => $tienda->fecha_inscripcion,
                'fecha_inicio_actividades' => $tienda->fecha_inicio_actividades,
                'ciiu' => $tienda->ciiu,
                'actividad_comercio_exterior' => $tienda->actividad_comercio_exterior,
                'verificado' => $tienda->verificado,
                'verificado_at' => $tienda->verificado_at,
                'creado_por' => $tienda->creado_por,
                'editado_por' => $tienda->editado_por,
            ]);

            $this->mapaTiendaAProveedor[$tienda->id] = $proveedor->id;
        }
    }

    private function nombreClaveTienda(object $tienda): string
    {
        return trim($tienda->razon_social ?: $tienda->nombre_comercial ?: "Tienda comercial #{$tienda->id}");
    }
    private function generarCodigoPersona(string $base): string
    {
        $prefijo = 'PROV-';
        $maxLongitudTotal = 30;
        $maxLongitudSlug = $maxLongitudTotal - strlen($prefijo);

        $slug = Str::upper(Str::slug($base, '-'));
        $slug = Str::substr($slug, 0, $maxLongitudSlug);
        $slug = trim($slug, '-'); // por si el corte deja un guion colgando al final

        $codigo = $prefijo . $slug;

        // Si hay que desambiguar, el sufijo debe caber también dentro del límite total
        $intento = $codigo;
        $sufijo = 1;
        while (Persona::where('codigo', $intento)->exists()) {
            $sufijoStr = "-{$sufijo}";
            $espacioDisponible = $maxLongitudTotal - strlen($sufijoStr);
            $intento = Str::substr($codigo, 0, $espacioDisponible) . $sufijoStr;
            $sufijo++;
        }

        return $intento;
    }
    /**
     * Si hay RUC lo usa tal cual; si no, genera un número de 11 dígitos
     * bajo un tipo_documento distinto para no aparentar ser un RUC real.
     */
    private function resolverDocumento(?string $ruc): array
    {
        if (!empty($ruc)) {
            return ['RUC', $ruc];
        }

        do {
            $numero = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        } while (Persona::where('tipo_documento', 'SIN_DOCUMENTO')->where('numero_documento', $numero)->exists());

        return ['SIN_DOCUMENTO', $numero];
    }

    // ──────────────────────────────────────────────────────────
    // 2. compra_productos -> compras + compra_detalles
    // ──────────────────────────────────────────────────────────
    private function migrarCompras(): void
    {
        $almacenId = DB::table('almacenes')->value('id');
        if (!$almacenId) {
            throw new \RuntimeException('No hay almacén configurado; no se puede migrar compras.');
        }

        $todas = DB::table('compra_productos')->orderBy('id')->get();
        $grupos = $this->agruparPorComprobante($todas);

        foreach ($grupos as $grupo) {
            $marca = 'MIGRADO_CP:' . implode(',', $grupo->pluck('id')->all());
            if (DB::table('compras')->where('notas', 'like', "%[{$marca}]%")->exists()) {
                continue;
            }

            $primera = $grupo->first();

            $proveedorId = $primera->tienda_comercial_id
                ? ($this->mapaTiendaAProveedor[$primera->tienda_comercial_id] ?? $this->obtenerOCrearProveedorGenerico())
                : $this->obtenerOCrearProveedorGenerico();

            $totalHeader = round((float) $grupo->sum('total'), 4);
            $subtotalNeto = round($totalHeader / 1.18, 4);
            $igvTotal = round($totalHeader - $subtotalNeto, 4);

            $notasOrden = $grupo->pluck('orden_compra')->filter()->unique()->implode(', ');
            $notas = trim(($notasOrden ? "Orden(es) de compra: {$notasOrden}. " : '') . "[{$marca}]");

            $compraId = DB::table('compras')->insertGetId([
                'proveedor_id' => $proveedorId,
                'almacen_id' => $almacenId,
                'moneda' => 'PEN',
                'tipo_cambio' => 1.0000,
                'tipo_comprobante_codigo' => $primera->tipo_compra_codigo,
                'serie' => $primera->serie,
                'numero' => $primera->numero,
                'fecha_emision' => $primera->fecha_compra,
                'fecha_vencimiento' => $primera->fecha_termino,
                'forma_pago' => 'contado',
                'tipo_kardex' => $primera->tipo_kardex,
                'subtotal_neto' => $subtotalNeto,
                'igv_total' => $igvTotal,
                'total' => $totalHeader,
                'notas' => $notas,
                'creado_por' => $primera->creado_por,
                'editado_por' => $primera->editado_por,
                'eliminado_por' => $primera->eliminado_por,
                'created_at' => $primera->created_at,
                'updated_at' => $primera->updated_at,
                'deleted_at' => $primera->deleted_at,
            ]);

            foreach ($grupo as $cp) {
                $stock = (float) $cp->stock;
                $total = (float) $cp->total;
                $costoUnitario = $stock > 0 ? round($total / $stock, 4) : 0;

                DB::table('compra_detalles')->insert([
                    'compra_id' => $compraId,
                    'producto_id' => $cp->producto_id,
                    'presentacion_id' => null,
                    'nombre_presentacion' => null,
                    'cantidad' => $stock,
                    'costo_unitario' => $costoUnitario,
                    'porcentaje_descuento' => 0,
                    'porcentaje_igv' => 18.00,
                    'total_linea' => $total,
                    'factor_conversion_usado' => 1.0000,
                    'cantidad_base' => $stock,
                    'costo_unitario_base' => $costoUnitario,
                    'created_at' => $cp->created_at,
                    'updated_at' => $cp->updated_at,
                ]);
            }
        }
    }

    /**
     * Agrupa por serie+numero+tienda_comercial_id. Filas con serie o numero
     * vacíos jamás se agrupan (cada una es su propio grupo). Si dentro de una
     * clave hay fecha_compra o tipo_kardex inconsistentes, la fila divergente
     * se separa a su propio grupo en vez de forzarse.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    private function agruparPorComprobante($filas)
    {
        $porClave = [];

        foreach ($filas as $fila) {
            $serie = trim((string) $fila->serie);
            $numero = trim((string) $fila->numero);

            $clave = ($serie !== '' && $numero !== '')
                ? "{$serie}|{$numero}|{$fila->tienda_comercial_id}"
                : "single:{$fila->id}";

            $porClave[$clave][] = $fila;
        }

        $gruposFinales = collect();

        foreach ($porClave as $clave => $filasGrupo) {
            $coleccion = collect($filasGrupo);

            if ($coleccion->count() === 1) {
                $gruposFinales->push($coleccion);
                continue;
            }

            // Verificar consistencia de fecha_compra y tipo_kardex dentro del grupo
            $porFechaYTipo = $coleccion->groupBy(fn($f) => $f->fecha_compra . '|' . $f->tipo_kardex);

            if ($porFechaYTipo->count() > 1) {
                $this->command?->warn(
                    "Comprobante {$clave} tiene fecha/tipo_kardex inconsistentes entre sus filas "
                    . "(IDs: " . $coleccion->pluck('id')->implode(',') . "). Se separó en sub-grupos."
                );
            }

            foreach ($porFechaYTipo as $subGrupo) {
                $gruposFinales->push($subGrupo);
            }
        }

        return $gruposFinales;
    }


    private function obtenerOCrearProveedorGenerico(): int
    {
        if ($this->proveedorGenericoId) {
            return $this->proveedorGenericoId;
        }

        $nombre = 'PROVEEDOR NO ESPECIFICADO';
        $persona = Persona::where('razon_social', $nombre)->first();

        if (!$persona) {
            [$tipoDocumento, $numeroDocumento] = $this->resolverDocumento(null);
            $persona = Persona::create([
                'codigo' => $this->generarCodigoPersona($nombre),
                'tipo' => 'empresa',
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'razon_social' => $nombre,
                'nombre_mostrar' => $nombre,
                'activo' => true,
            ]);
        }

        $proveedor = Proveedor::where('persona_id', $persona->id)->first()
            ?? Proveedor::create(['persona_id' => $persona->id]);

        return $this->proveedorGenericoId = $proveedor->id;
    }
}