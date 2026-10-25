<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Producción de campo: fuente única de las cantidades (kilos, lavadores, cajas…) que produce cada trabajador en una
 * actividad (fecha + campo + labor), por recojo. Con bono (las ingresa bonificaciones) o sin bono (se registran
 * aparte). El trabajador es opcional: puede ser un cuadrillero, un empleado de planilla o nadie (solo el total).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('cuadrillero_id')->nullable()->constrained('cuad_cuadrilleros')->nullOnDelete();
            $table->foreignId('plan_empleado_id')->nullable()->constrained('plan_empleados')->nullOnDelete();
            $table->unsignedInteger('numero_recojo')->default(1);
            $table->decimal('cantidad', 10, 2);
            // Unidad con que se registró (código del catálogo, copiada de la labor)
            $table->string('unidad', 20)->nullable();
            $table->boolean('con_bono')->default(false);
            $table->string('observacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['actividad_id', 'cuadrillero_id']);
            $table->index(['actividad_id', 'plan_empleado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_registros');
    }
};
