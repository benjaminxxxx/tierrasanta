<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuadrilla_asistencia_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrilla_asistencia_id')->constrained()->onDelete('cascade');
            $table->string('codigo');
            $table->string('color');
            $table->string('nombre');
            $table->decimal('costo_dia', 8, 2);
            $table->enum('modalidad_pago',['mensual','quincenal','semanal','variado'])->default('mensual');
            $table->decimal('total_costo', 10, 2)->nullable(); // Calculado basado en trabajadores
            $table->string('numero_recibo')->nullable();
            $table->date('fecha_pagado')->nullable();
            $table->enum('condicion', ['pagado', 'pendiente'])->default('pendiente');
            $table->decimal('dinero_recibido', 10, 2)->nullable();
            $table->decimal('saldo', 10, 2)->nullable();
            $table->decimal('adelanto', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_asistencia_grupos');
    }
};
