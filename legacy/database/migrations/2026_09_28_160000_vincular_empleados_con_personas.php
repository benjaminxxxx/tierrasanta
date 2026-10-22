<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Fase de transición plan_empleados -> personas (datos personales).
     *
     * - Crea la persona de los empleados que aún no la tienen (también los eliminados: la persona
     *   nunca se borra físicamente) o los vincula a una persona existente con el mismo DNI.
     * - Completa telefono_movil desde plan_empleados.numero cuando la persona no lo tiene.
     *
     * Las columnas de plan_empleados NO se eliminan aquí: siguen como espejo mientras el resto del
     * sistema termina de leer desde personas. Es idempotente: se puede correr más de una vez.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $sinPersona = DB::table('plan_empleados')->whereNull('persona_id')->get();

            foreach ($sinPersona as $e) {
                $existente = $e->documento
                    ? DB::table('personas')->where('tipo_documento', 'DNI')->where('numero_documento', $e->documento)->first()
                    : null;

                $personaId = $existente?->id ?? DB::table('personas')->insertGetId([
                    'codigo' => 'PLA-' . $e->id,
                    'tipo' => 'individual',
                    'tipo_documento' => 'DNI',
                    'numero_documento' => $e->documento,
                    'nombres' => $e->nombres,
                    'apellido_paterno' => $e->apellido_paterno,
                    'apellido_materno' => $e->apellido_materno,
                    'nombre_mostrar' => trim(implode(' ', array_filter([$e->apellido_paterno, $e->apellido_materno, $e->nombres]))),
                    'fecha_nacimiento' => $e->fecha_nacimiento,
                    'genero' => match ($e->genero) {
                        'M' => 'masculino',
                        'F' => 'femenino',
                        default => null,
                    },
                    'telefono_movil' => $e->numero,
                    'email' => $e->email,
                    'direccion' => $e->direccion,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('plan_empleados')->where('id', $e->id)->update(['persona_id' => $personaId]);
            }

            // Teléfono: antes no se sincronizaba a personas
            DB::table('personas as p')
                ->join('plan_empleados as e', 'e.persona_id', '=', 'p.id')
                ->whereNull('p.telefono_movil')
                ->whereNotNull('e.numero')
                ->update(['p.telefono_movil' => DB::raw('e.numero')]);
        });
    }

    /**
     * No se revierte: las personas nunca se eliminan físicamente.
     */
    public function down(): void
    {
    }
};
