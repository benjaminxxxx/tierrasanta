<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zona del campo donde se regó (ej. "Baja", "Ingreso"): dos regadores en el mismo campo y horario pero en zonas
 * distintas no son riego cruzado. Vacía = zona general del campo. Solo afecta el aviso de cruce, no el costo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reg_registro_diario', 'zona')) {
            Schema::table('reg_registro_diario', function (Blueprint $table) {
                $table->string('zona', 40)->nullable()->after('campo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reg_registro_diario', 'zona')) {
            Schema::table('reg_registro_diario', fn(Blueprint $table) => $table->dropColumn('zona'));
        }
    }
};
