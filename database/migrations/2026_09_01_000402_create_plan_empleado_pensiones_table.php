<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partícula del empleado: sistema de pensión desde una fecha (la afiliación es de la persona y sigue al recontratarla).
 * Cambiar de AFP u ONP no cierra el contrato. Vale hasta la siguiente fila del mismo empleado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_empleado_pensiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->date('desde');
            $table->string('plan_sp_codigo')->nullable();
            $table->boolean('esta_jubilado')->default(false);
            $table->boolean('suspension_cuarta')->default(false);
            $table->string('motivo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_empleado_id', 'desde']);
            $table->foreign('plan_sp_codigo')->references('codigo')->on('plan_sp_desc')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_empleado_pensiones');
    }
};
