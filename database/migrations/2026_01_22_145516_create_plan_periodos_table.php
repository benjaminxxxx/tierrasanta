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
        Schema::create('plan_periodos', function (Blueprint $table) {
            $table->id();

            // 🔹 Relación principal
            $table->foreignId('plan_empleado_id')
                ->constrained('plan_empleados')
                ->cascadeOnDelete();

            // 🔹 Evento histórico
            $table->string('codigo', 10);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // 🔹 Contexto funcional
            $table->text('observaciones')->nullable();

            // 🔹 Auditoría de modificaciones
            $table->text('motivo_modificacion')->nullable();
            $table->foreignId('modificado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // 🔹 Auditoría de eliminación lógica
            $table->text('motivo_eliminacion')->nullable();
            $table->foreignId('eliminado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // 🔹 Soft delete histórico
            $table->softDeletes();

            $table->timestamps();

            // 🔹 Protección mínima
            $table->index(['plan_empleado_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_periodos');
    }
};
