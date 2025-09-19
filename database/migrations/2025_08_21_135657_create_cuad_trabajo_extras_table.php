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
        Schema::create('cuad_trabajos_extra', function (Blueprint $table) {
             $table->id();
            $table->foreignId('cuadrillero_id')->constrained('cuadrilleros')->cascadeOnDelete();
            $table->date('fecha');
            $table->boolean('esta_pagado')->default(false);

            $table->decimal('horas', 8, 2)->default(0);
            $table->decimal('costo_x_hora', 10, 2)->default(0);
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->integer('orden');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_trabajos_extra');
    }
};
