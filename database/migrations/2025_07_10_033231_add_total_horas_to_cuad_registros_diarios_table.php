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
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->decimal('total_horas', 5, 2)->nullable()->after('asistencia');
            $table->unique(['cuadrillero_id', 'fecha'], 'cuad_registros_diarios_cuadrillero_fecha_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropColumn('total_horas');
            $table->dropUnique('cuad_registros_diarios_cuadrillero_fecha_unique');
        });
    }
};
