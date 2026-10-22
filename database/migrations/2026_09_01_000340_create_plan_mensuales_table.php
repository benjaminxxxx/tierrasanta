<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_mensuales', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->integer('dias_laborables')->nullable();
            $table->integer('total_horas')->nullable();
            $table->integer('total_empleados')->nullable();
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->text('excel')->nullable();
            $table->string('excel_oficina')->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('gratificaciones', 10, 2)->nullable();
            $table->decimal('essalud_gratificaciones', 10, 2)->nullable();
            $table->decimal('cts', 10, 2)->nullable();
            $table->decimal('rmv', 10, 2)->nullable();
            $table->decimal('beta30', 10, 2)->nullable();
            $table->decimal('essalud', 10, 2)->nullable();
            $table->decimal('essalud_general', 5, 2)->nullable();
            $table->decimal('bonif_extraordinaria_general', 5, 2)->nullable();
            $table->decimal('retencion_cuarta', 5, 2)->nullable();
            $table->decimal('tope_retencion_cuarta', 10, 2)->nullable();
            $table->decimal('vida_ley', 10, 2)->nullable();
            $table->decimal('pension_sctr', 10, 2)->nullable();
            $table->decimal('essalud_eps', 10, 2)->nullable();
            $table->decimal('rem_basica_essalud', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mensuales');
    }
};
