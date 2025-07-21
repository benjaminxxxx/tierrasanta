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
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->string('codigo_grupo')->after('cua_asistencia_semanal_grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->dropColumn(['codigo_grupo']);
        });
    }
};
