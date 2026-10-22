<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costo_mano_indirectas', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('blanco_cuadrillero_monto', 14, 6)->nullable();
            $table->string('blanco_cuadrillero_file')->nullable();
            $table->decimal('negro_cuadrillero_monto', 14, 6)->nullable();
            $table->string('negro_cuadrillero_file')->nullable();
            $table->decimal('blanco_planillero_monto', 14, 6)->nullable();
            $table->string('blanco_planillero_file')->nullable();
            $table->decimal('negro_planillero_monto', 14, 6)->nullable();
            $table->string('negro_planillero_file')->nullable();
            $table->decimal('blanco_maquinaria_monto', 14, 6)->nullable();
            $table->string('blanco_maquinaria_file')->nullable();
            $table->decimal('negro_maquinaria_monto', 14, 6)->nullable();
            $table->string('negro_maquinaria_file')->nullable();
            $table->decimal('blanco_maquinaria_salida_monto', 14, 6)->nullable();
            $table->string('blanco_maquinaria_salida_file')->nullable();
            $table->decimal('negro_maquinaria_salida_monto', 14, 6)->nullable();
            $table->string('negro_maquinaria_salida_file')->nullable();
            $table->decimal('blanco_costos_adicionales_monto', 14, 6)->nullable();
            $table->string('blanco_costos_adicionales_file')->nullable();
            $table->decimal('negro_costos_adicionales_monto', 14, 6)->nullable();
            $table->string('negro_costos_adicionales_file')->nullable();
            $table->decimal('negro_cuadrillero_bono', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costo_mano_indirectas');
    }
};
