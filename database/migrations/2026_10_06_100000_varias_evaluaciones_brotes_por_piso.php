<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brotes por piso: varias evaluaciones por campaña (antes solo una), una por fecha. Los metros de cama por
 * hectárea siguen en cada evaluación, pero son los mismos para todas las de una campaña (los mantiene el servicio).
 * Cada evaluación guarda quién la registró y quién la editó por última vez.
 */
return new class extends Migration {
    public function up(): void
    {
        // Evaluaciones antiguas sin fecha: la de su registro, para que la fecha pueda identificarlas
        DB::table('eval_brotes_por_pisos')->whereNull('fecha')->update(['fecha' => DB::raw('DATE(created_at)')]);

        Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
            // Primero el índice nuevo: la llave foránea de campania_id se apoya en él al quitar el único anterior
            $table->unique(['campania_id', 'fecha'], 'eval_brotes_campania_fecha_unique');
        });
        Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
            $table->dropUnique('eval_brotes_por_pisos_campania_id_unique');
            $table->foreignId('creado_por')->nullable()->after('evaluador')->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->after('creado_por')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actualizado_por');
            $table->dropConstrainedForeignId('creado_por');
            $table->unique('campania_id');
        });
        Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
            $table->dropUnique('eval_brotes_campania_fecha_unique');
        });
    }
};
