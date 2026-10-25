<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contrato (cabecera): el periodo legal. Lo que cambia aquí es otro contrato (pasar de agraria a seguridad, por
 * ejemplo). Puede haber días sin contrato entre uno y otro (despido y recontratación). Lo que cambia dentro del mismo
 * contrato va en sus partículas: asignaciones (cargo, grupo) y remuneraciones (sueldo, básica, bonificación…).
 * El estado (activo, en prueba, finalizado) se calcula con las fechas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->enum('tipo_contrato', ['plazo fijo', 'indefinido', 'temporal'])->default('indefinido');
            // externa: se registra (albañiles, seguridad…) pero el sistema no le calcula planilla
            $table->enum('tipo_planilla', ['agraria', 'oficina', 'externa'])->default('agraria');
            $table->string('tipo_ingreso', 20)->default('planilla');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_fin_prueba')->nullable();
            $table->string('motivo_cese_sunat', 2)->nullable();
            $table->text('comentario_cese')->nullable();
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['plan_empleado_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_contratos');
    }
};
