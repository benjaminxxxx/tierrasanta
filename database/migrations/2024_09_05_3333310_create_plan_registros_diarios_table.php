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
        Schema::create('plan_registros_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_det_men_id')
                ->constrained('plan_mensual_detalles')
                ->onDelete('cascade'); // vinculado a plan_mensual_detalles.id para al eliminar eliminar todos los reportes diarios
            $table->string('asistencia', 6);
            $table->date('fecha'); // fecha
            $table->decimal('total_bono', 10, 2)->nullable();
            $table->decimal('costo_dia', 10, 2)->nullable();
            $table->decimal('total_horas',10, 2);
            $table->boolean('esta_pagado')->default(false);
            $table->boolean('bono_esta_pagado')->default(false);
            $table->timestamps();
            $table->unique(['plan_det_men_id', 'fecha'], 'unique_plan_det_men_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_registros_diarios');
    }
};
