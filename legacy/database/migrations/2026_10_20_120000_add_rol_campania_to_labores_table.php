<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rol de la labor en la campaña (antes eran listas fijas en CampaniaCosechaReglas):
 * - cosecha: marca la cosecha (fin de la campaña): poda, poda mamá, cosecha mamá, traslados de cosecha…
 * - neutra: no indica campaña (administración, vigilancia, maquinaria…).
 * - null: labor normal; después de la cosecha indica que ya empezó la campaña siguiente.
 * Se llena con las listas que se usaban (o la que hubiera guardada en configuracion).
 */
return new class extends Migration {
    private const COSECHA = [41, 42, 52, 67, 68, 144, 145, 146];
    private const NEUTRAS = [79, 80, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100,
        101, 102, 103, 104, 111, 112, 113, 114, 115, 116, 117, 122, 133, 140, 142, 143, 155, 158];

    public function up(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->string('rol_campania', 10)->nullable()->after('tipo_asistencia_codigo');
        });

        $lista = function (string $config, array $defecto) {
            $valor = Schema::hasTable('configuracion') ? DB::table('configuracion')->where('codigo', $config)->value('valor') : null;
            $codigos = $valor ? array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', $valor)))) : [];
            // La post-cosecha (69) ya no es cosecha: es de la campaña siguiente
            return array_values(array_diff($codigos ?: $defecto, $config === 'campania_labores_cosecha' ? [69] : []));
        };
        DB::table('labores')->whereIn('codigo', $lista('campania_labores_neutras', self::NEUTRAS))->update(['rol_campania' => 'neutra']);
        DB::table('labores')->whereIn('codigo', $lista('campania_labores_cosecha', self::COSECHA))->update(['rol_campania' => 'cosecha']);
    }

    public function down(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->dropColumn('rol_campania');
        });
    }
};
