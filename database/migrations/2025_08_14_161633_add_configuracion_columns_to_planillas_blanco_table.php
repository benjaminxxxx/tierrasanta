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
        Schema::table('planillas_blanco', function (Blueprint $table) {
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('cts_porcentaje', 10, 2)->nullable();
            $table->decimal('gratificaciones', 10, 2)->nullable();
            $table->decimal('essalud_gratificaciones', 10, 2)->nullable();
            $table->decimal('rmv', 10, 2)->nullable();
            $table->decimal('beta30', 10, 2)->nullable();
            $table->decimal('essalud', 10, 2)->nullable();
            $table->decimal('vida_ley', 10, 2)->nullable();
            $table->decimal('vida_ley_porcentaje', 10, 2)->nullable();
            $table->decimal('pension_sctr', 10, 2)->nullable();
            $table->decimal('pension_sctr_porcentaje', 10, 2)->nullable();
            $table->decimal('essalud_eps', 10, 2)->nullable();
            $table->decimal('porcentaje_constante', 10, 2)->nullable();
            $table->decimal('rem_basica_essalud', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planillas_blanco', function (Blueprint $table) {
            $table->dropColumn([
                'asignacion_familiar',
                'cts_porcentaje',
                'gratificaciones',
                'essalud_gratificaciones',
                'rmv',
                'beta30',
                'essalud',
                'vida_ley',
                'vida_ley_porcentaje',
                'pension_sctr',
                'pension_sctr_porcentaje',
                'essalud_eps',
                'porcentaje_constante',
                'rem_basica_essalud'
            ]);
        });
    }
};
