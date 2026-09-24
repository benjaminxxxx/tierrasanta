<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrarTiendasAProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Obtener el último RUC temporal existente para no colisionar si se vuelve a ejecutar
            $ultimoRucTemp = DB::table('personas')
                ->where('numero_documento', 'like', '990%')
                ->max('numero_documento');

            $secuenciaRucTemp = $ultimoRucTemp ? (int) $ultimoRucTemp : 99000000000;

            DB::table('tienda_comercials')->orderBy('id')->cursor()->each(function ($tienda, $index) use (&$secuenciaRucTemp) {
                
                $rucReal = !empty(trim($tienda->ruc ?? '')) ? trim($tienda->ruc) : null;

                $nombre = !empty(trim($tienda->nombre_comercial ?? ''))
                    ? trim($tienda->nombre_comercial)
                    : ($tienda->razon_social ?? 'TIENDA SIN NOMBRE');

                $razonSocial = !empty(trim($tienda->razon_social ?? ''))
                    ? trim($tienda->razon_social)
                    : $nombre;

                $persona = null;

                // ESTRATEGIA DE BÚSQUEDA ANTI-DUPLICADOS:
                
                // Paso 1: Si la tienda tiene un RUC real (distinto a nulo), buscamos por RUC
                if ($rucReal) {
                    $persona = Persona::where('tipo_documento', 'RUC')
                        ->where('numero_documento', $rucReal)
                        ->first();
                }

                // Paso 2: Si no tiene RUC real o no se encontró por RUC, buscamos por NOMBRE (coincidencia exacta limpia)
                if (!$persona) {
                    $persona = Persona::where(function ($q) use ($nombre, $razonSocial) {
                        $q->whereRaw('LOWER(TRIM(nombre_mostrar)) = ?', [mb_strtolower(trim($nombre))])
                          ->orWhereRaw('LOWER(TRIM(razon_social)) = ?', [mb_strtolower(trim($razonSocial))]);
                    })->first();
                }

                // Paso 3: Si no existe por RUC ni por Nombre, se crea una nueva Persona
                if (!$persona) {
                    // Si no tenía RUC real, le asignamos el siguiente RUC temporal
                    $documentoFinal = $rucReal;
                    if (!$documentoFinal) {
                        $secuenciaRucTemp++;
                        $documentoFinal = (string) $secuenciaRucTemp;
                    }

                    $persona = Persona::create([
                        'tipo_documento'   => 'RUC',
                        'numero_documento' => $documentoFinal,
                        'codigo'           => 'PER-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
                        'tipo'             => 'empresa',
                        'razon_social'     => $razonSocial,
                        'nombre_mostrar'   => $nombre,
                        'nombre_legal'     => $razonSocial,
                        'telefono_movil'   => $tienda->contacto ?? null,
                        'direccion'        => $tienda->direccion_fiscal ?? null,
                        'distrito'         => $tienda->distrito ?? null,
                        'provincia'        => $tienda->provincia ?? null,
                        'departamento'     => $tienda->departamento ?? null,
                        'activo'           => true,
                    ]);
                } else {
                    // Si la persona ya existía por nombre y ahora encontramos que esta tienda traía RUC real, actualizamos su RUC
                    if ($rucReal && str_starts_with($persona->numero_documento, '990')) {
                        $persona->update([
                            'numero_documento' => $rucReal,
                        ]);
                    }
                }

                // 4. Crear o asociar la entrada en la tabla proveedores
                Proveedor::updateOrCreate(
                    [
                        'persona_id' => $persona->id,
                    ],
                    [
                        'tipo_contribuyente'          => $tienda->tipo_contribuyente ?? null,
                        'condicion'                   => $tienda->condicion ?? null,
                        'estado_contribuyente'        => $tienda->estado_contribuyente ?? null,
                        'estado_domicilio'            => $tienda->estado_domicilio ?? null,
                        'fecha_inscripcion'           => $tienda->fecha_inscripcion ?? null,
                        'fecha_inicio_actividades'    => $tienda->fecha_inicio_actividades ?? null,
                        'ciiu'                        => $tienda->ciiu ?? null,
                        'actividad_comercio_exterior' => $tienda->actividad_comercio_exterior ?? null,
                        'verificado'                  => $tienda->verificado ?? false,
                        'verificado_at'               => $tienda->verificado_at ?? null,
                        'creado_por'                  => $tienda->creado_por ?? null,
                        'editado_por'                 => $tienda->editado_por ?? null,
                        'eliminado_por'               => $tienda->eliminado_por ?? null,
                    ]
                );
            });
        });
    }
}