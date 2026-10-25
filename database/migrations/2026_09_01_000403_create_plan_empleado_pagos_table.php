<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partícula del empleado: cómo y dónde se le paga desde una fecha (cuenta principal y la de la diferencia).
 * Sigue al empleado entre contratos. Vale hasta la siguiente fila del mismo empleado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_empleado_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->date('desde');
            $table->string('metodo_pago', 20)->nullable();
            $table->string('banco', 40)->nullable();
            $table->string('tipo_cuenta', 20)->nullable();
            $table->string('moneda_cuenta', 3)->nullable();
            $table->string('numero_cuenta', 40)->nullable();
            $table->string('banco_secundario', 40)->nullable();
            $table->string('tipo_cuenta_secundaria', 20)->nullable();
            $table->string('moneda_cuenta_secundaria', 3)->nullable();
            $table->string('numero_cuenta_secundaria', 40)->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_empleado_id', 'desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_empleado_pagos');
    }
};
