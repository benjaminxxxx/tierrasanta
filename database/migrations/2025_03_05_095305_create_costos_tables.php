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
        Schema::create('costos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->integer('mes');
            $table->unique(['anio', 'mes']); // Evita duplicados para el mismo mes y año

            // Costos Fijos
            $table->decimal('fijo_administrativo_blanco', 10, 2)->nullable();
            $table->decimal('fijo_administrativo_negro', 10, 2)->nullable();
            $table->decimal('fijo_financiero_blanco', 10, 2)->nullable();
            $table->decimal('fijo_financiero_negro', 10, 2)->nullable();
            $table->decimal('fijo_gastos_oficina_blanco', 10, 2)->nullable();
            $table->decimal('fijo_gastos_oficina_negro', 10, 2)->nullable();
            $table->decimal('fijo_depreciaciones_blanco', 10, 2)->nullable();
            $table->decimal('fijo_depreciaciones_negro', 10, 2)->nullable();
            $table->decimal('fijo_costo_terreno_blanco', 10, 2)->nullable();
            $table->decimal('fijo_costo_terreno_negro', 10, 2)->nullable();

            // Costos Operativos
            $table->decimal('operativo_servicios_fundo_blanco', 10, 2)->nullable();
            $table->decimal('operativo_servicios_fundo_negro', 10, 2)->nullable();
            $table->decimal('operativo_mano_obra_indirecta_blanco', 10, 2)->nullable();
            $table->decimal('operativo_mano_obra_indirecta_negro', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costos_mensuales');
    }
};
