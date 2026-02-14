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
        Schema::create('plan_mensuales', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->integer('dias_laborables')->nullable();
            $table->integer('total_horas')->nullable();
            $table->integer('total_empleados')->nullable();
            $table->decimal('factor_remuneracion_basica', 15, 12)->nullable();
            $table->text('excel')->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('cts_porcentaje', 10, 2)->nullable();
            $table->decimal('gratificaciones', 10, 2)->nullable();
            $table->decimal('essalud_gratificaciones', 10, 2)->nullable();
            $table->decimal('cts', 10, 2)->nullable();
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

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mensuales');
    }
};
