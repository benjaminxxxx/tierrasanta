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
        Schema::create('plan_suspensiones', function (Blueprint $table) {
            $table->id();
            // Relación con empleado
            $table->foreignId('plan_empleado_id')
                ->constrained('plan_empleados')
                ->restrictOnDelete();

            // Relación con tipo de suspensión SUNAT
            $table->foreignId('tipo_suspension_id')
                ->constrained('plan_tipos_suspension')
                ->restrictOnDelete();

            // Fechas de la suspensión
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // Null = suspensión indefinida o en curso

            // Información adicional
            $table->text('observaciones')->nullable();
            $table->string('documento_respaldo')->nullable(); // Ruta al documento (certificado médico, etc.)

            // Auditoría
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índices para mejorar performance en consultas
            $table->index(['plan_empleado_id', 'fecha_inicio', 'fecha_fin'], 'idx_suspensiones_empleado_fechas');
            $table->index('tipo_suspension_id');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_suspensiones');
    }
};
