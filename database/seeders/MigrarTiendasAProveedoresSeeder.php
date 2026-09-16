<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Persona;
use App\Models\Proveedor;
use Illuminate\Support\Str;

class MigrarTiendasAProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $tiendas = DB::table('tienda_comercials')->get();

            // RUC temporal de 11 dígitos para los que no tengan RUC
            $secuenciaRucTemp = 99000000000;

            

            foreach ($tiendas as $index => $tienda) {

                // 1. Obtener o generar el RUC (11 dígitos)
                $ruc = !empty($tienda->ruc) ? trim($tienda->ruc) : null;

                if (!$ruc) {
                    $secuenciaRucTemp++;
                    $ruc = (string) $secuenciaRucTemp;
                }

                $nombre = !empty($tienda->nombre_comercial)
                    ? trim($tienda->nombre_comercial)
                    : ($tienda->razon_social ?? 'TIENDA SIN NOMBRE');

                // 2. Buscar o crear el registro en la tabla personas
                $persona = Persona::firstOrCreate(
                    [
                        'tipo_documento' => 'RUC',
                        'numero_documento' => $ruc,
                    ],
                    [
                        'codigo' => 'PER-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '-' . Str::random(3),
                        'tipo' => 'empresa', 
                        'razon_social' => $tienda->razon_social ?? $nombre,
                        'nombre_mostrar' => $nombre,
                        'nombre_legal' => $tienda->razon_social ?? $nombre,
                        'telefono_movil' => $tienda->contacto ?? null,
                        'direccion' => $tienda->direccion_fiscal ?? null,
                        'distrito' => $tienda->distrito ?? null,
                        'provincia' => $tienda->provincia ?? null,
                        'departamento' => $tienda->departamento ?? null,
                        'activo' => true,
                    ]
                );

                // 3. Crear o actualizar la entrada correspondiente en proveedores
                Proveedor::updateOrCreate(
                    [
                        'persona_id' => $persona->id,
                    ],
                    [
                        'tipo_contribuyente' => $tienda->tipo_contribuyente ?? null,
                        'condicion' => $tienda->condicion ?? null,
                        'estado_contribuyente' => $tienda->estado_contribuyente ?? null,
                        'estado_domicilio' => $tienda->estado_domicilio ?? null,
                        'fecha_inscripcion' => $tienda->fecha_inscripcion ?? null,
                        'fecha_inicio_actividades' => $tienda->fecha_inicio_actividades ?? null,
                        'ciiu' => $tienda->ciiu ?? null,
                        'actividad_comercio_exterior' => $tienda->actividad_comercio_exterior ?? null,
                        'verificado' => $tienda->verificado ?? false,
                        'verificado_at' => $tienda->verificado_at ?? null,
                        'creado_por' => $tienda->creado_por ?? null,
                        'editado_por' => $tienda->editado_por ?? null,
                        'eliminado_por' => $tienda->eliminado_por ?? null,
                    ]
                );
            }
        });
    }
}