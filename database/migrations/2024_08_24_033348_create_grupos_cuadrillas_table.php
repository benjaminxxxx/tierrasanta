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
        Schema::create('grupos_cuadrilla', function (Blueprint $table) {
            $table->string('codigo')->primary();
            $table->string('color');
            $table->string('nombre');
            $table->enum('modalidad_pago',['mensual','quincenal','semanal','variado'])->default('mensual');
            $table->decimal('costo_dia_sugerido', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_cuadrillas');
    }
};