<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reg_resumen', function (Blueprint $table) {
            // Crea trabajador_id (BIGINT) + trabajador_type (VARCHAR)
            // Y agrega índice compuesto
            $table->morphs('trabajador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reg_resumen', function (Blueprint $table) {
            $table->dropMorphs('trabajador');
        });
    }
};
