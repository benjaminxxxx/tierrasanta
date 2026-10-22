<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_empleado_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->foreignId('plan_cargo_id')->constrained('plan_cargos');
            $table->string('grupo_codigo')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();
            $table->string('motivo_cambio')->nullable();
            $table->string('creado_por')->nullable();
            $table->timestamps();

            $table->index(['plan_cargo_id', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_empleado_cargos');
    }
};
