<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mes de planilla: días laborables, horas y sueldo base del mes, y los Excel generados. Las tasas (RMV, EsSalud,
 * gratificaciones, AFP…) no se copian aquí: se leen de Parámetros de planilla (PlanillaParametroConsulta::delMes) y
 * quedan con candado cuando una planilla las usa.
 */
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
            $table->timestamps();

            $table->unique(['mes', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mensuales');
    }
};
