<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_suspensiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->restrictOnDelete();
            $table->foreignId('tipo_suspension_id')->constrained('plan_tipos_suspension')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('documento_respaldo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['plan_empleado_id', 'fecha_inicio', 'fecha_fin'], 'idx_suspensiones_empleado_fechas');
            $table->index('fecha_inicio');
            $table->index('tipo_suspension_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_suspensiones');
    }
};
