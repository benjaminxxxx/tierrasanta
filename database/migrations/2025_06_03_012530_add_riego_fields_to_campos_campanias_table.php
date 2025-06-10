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
        Schema::table('campos_campanias', function (Blueprint $table) {
            // Grupo: Riego
            $table->date('riego_inicio')->nullable(); // Fecha de inicio de riego
            $table->date('riego_fin')->nullable();    // Fecha de fin de riego

            $table->decimal('riego_descarga_ha_hora', 6, 2)->nullable(); // m3/há/hora

            $table->decimal('riego_hrs_ini_infest', 6, 2)->nullable();  // Horas de riego de inicio a infestación
            $table->decimal('riego_m3_ini_infest', 8, 2)->nullable();   // Metros cúbicos de inicio a infestación

            $table->decimal('riego_hrs_infest_reinf', 6, 2)->nullable(); // Horas de riego de infestación a reinfestación
            $table->decimal('riego_m3_infest_reinf', 8, 2)->nullable();  // Metros cúbicos de infestación a reinfestación

            $table->decimal('riego_hrs_reinf_cosecha', 6, 2)->nullable(); // Horas de riego de reinf a cosecha
            $table->decimal('riego_m3_reinf_cosecha', 8, 2)->nullable();  // Metros cúbicos de reinf a cosecha

            $table->decimal('riego_hrs_acumuladas', 6, 2)->nullable(); // Total de horas acumuladas de riego
            $table->decimal('riego_m3_acum_ha', 8, 2)->nullable();      // m3 acumulado por hectárea
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'riego_inicio',
                'riego_fin',
                'riego_descarga_ha',
                'riego_horas_ini_infest',
                'riego_m3_ini_infest',
                'riego_horas_infest_reinfest',
                'riego_m3_infest_reinfest',
                'riego_horas_infest_cosch',
                'riego_m3_infest_cosch',
                'riego_horas_acumuladas',
                'riego_m3_acumulado_ha',
            ]);
        });
    }
};
