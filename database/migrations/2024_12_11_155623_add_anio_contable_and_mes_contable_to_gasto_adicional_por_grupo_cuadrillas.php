<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->year('anio_contable')->nullable();  // Para el año contable
            $table->tinyInteger('mes_contable')->nullable();  // Para el mes contable (1-12)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->dropColumn(['anio_contable', 'mes_contable']);
        });
    }
};
