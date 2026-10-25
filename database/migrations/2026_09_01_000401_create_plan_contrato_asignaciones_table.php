<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partícula del contrato: cargo y grupo desde una fecha. Vale hasta la siguiente asignación del mismo contrato o hasta
 * el fin del contrato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_contrato_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_contrato_id')->constrained('plan_contratos')->cascadeOnDelete();
            $table->date('desde');
            $table->foreignId('plan_cargo_id')->nullable()->constrained('plan_cargos')->nullOnDelete();
            $table->string('grupo_codigo')->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_contrato_id', 'desde']);
            $table->foreign('grupo_codigo')->references('codigo')->on('plan_grupos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_contrato_asignaciones');
    }
};
