<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partícula del contrato: remuneración desde una fecha. Vale hasta la siguiente remuneración del mismo contrato o hasta
 * el fin del contrato. Cada fila es completa (no hereda valores de la anterior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_contrato_remuneraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_contrato_id')->constrained('plan_contratos')->cascadeOnDelete();
            $table->date('desde');
            $table->decimal('sueldo', 10, 2)->nullable();
            // Sueldo formal "blanco": si tiene valor reemplaza la remuneración básica general del mes
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->enum('modalidad_pago', ['mensual', 'quincenal', 'semanal'])->default('mensual');
            $table->boolean('beneficios_mensuales')->default(false);
            $table->string('motivo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_contrato_id', 'desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_contrato_remuneraciones');
    }
};
