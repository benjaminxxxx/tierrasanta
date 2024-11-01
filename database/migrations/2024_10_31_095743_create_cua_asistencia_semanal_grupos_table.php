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
        Schema::create('cua_asistencia_semanal_grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cua_asi_sem_id'); // Referencia a cua_asistencia_semanal->id
            $table->string('gru_cua_cod'); // Referencia a cua_grupos->codigo
            $table->decimal('costo_dia', 10, 2)->nullable();
            $table->decimal('costo_hora', 10, 2)->nullable();
            $table->string('numero_recibo')->nullable();
            $table->decimal('total_costo', 10, 2)->nullable();
            $table->date('fecha_pagado')->nullable();
            $table->decimal('dinero_recibido', 10, 2)->nullable();
            $table->decimal('saldo', 10, 2)->nullable();
            $table->decimal('total_pagado', 10, 2)->nullable();
            $table->timestamps();

            // Definición de las llaves foráneas
            $table->foreign('cua_asi_sem_id')
                ->references('id')
                ->on('cua_asistencia_semanal')
                ->onDelete('cascade');

            $table->foreign('gru_cua_cod')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cua_asistencia_semanal_grupos');
    }
};
