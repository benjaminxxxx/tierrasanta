<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuad_bonos_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_diario_id')
                ->constrained('cuad_registros_diarios')
                ->cascadeOnDelete();

            $table->foreignId('actividad_id')
                ->constrained('actividades')
                ->cascadeOnDelete();
            //nuevo campo que indique si se paga junto con el jornal o a parte
            $table->
            $table->decimal('total_bono', 10, 2)->default(0);
            $table->boolean('bono_manual')->default(false);

            // Configuración y Trazabilidad del Pago
            $table->boolean('se_paga_con_jornal')->default(true)->comment('true = junto con el jornal, false = se acumula');
            $table->boolean('esta_pagado')->default(false);
            $table->foreignId('desglose_detalle_id')
                ->nullable()
                ->constrained('desglose_detalles')
                ->nullOnDelete();

            $table->timestamps();

            // Un registro único por registro_diario y actividad
            $table->unique(['registro_diario_id', 'actividad_id'], 'registro_actividad_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_bonos_actividades');
    }
};
