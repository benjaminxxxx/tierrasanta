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
        Schema::create('pago_cuadrillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrillero_id')->constrained('cuadrilleros')->onDelete('cascade');
            $table->decimal('monto_trabajado', 10, 2);
            $table->decimal('monto_pagado', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago');
            $table->integer('anio_contable');
            $table->integer('mes_contable');
            $table->enum('estado', ['pendiente', 'pago_parcial', 'pago_completo'])->default('pendiente');
            $table->foreignId('pago_referencia_id')->nullable()->constrained('pago_cuadrillas')->onDelete('cascade'); // Autoreferencia
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_cuadrillas');
    }
};
