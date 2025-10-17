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
        Schema::create('cuad_registros_diarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuadrillero_id')
                ->constrained('cuad_cuadrilleros')
                ->restrictOnDelete();

            $table->date('fecha');
            $table->decimal('costo_personalizado_dia', 10, 2)->nullable();
            $table->decimal('total_bono', 10, 2)->default(0);
            $table->decimal('costo_dia', 10, 2)->default(0);

            //agregados despues
            $table->decimal('total_horas', 5, 2)->nullable();
            $table->boolean('esta_pagado')->default(false);
            $table->boolean('bono_esta_pagado')->default(false);
            $table->string('codigo_grupo');

            //mas adelante estan
            $table->unsignedBigInteger('tramo_laboral_id')->nullable();
            // Relación principal (cascade)
            $table->foreign('tramo_laboral_id')
                ->references('id')
                ->on('cuad_tramos_laborales')
                ->onDelete('cascade');

            $table->foreignId('tramo_pagado_jornal_id')
                ->nullable()
                ->constrained('cuad_tramos_laborales', 'id', 'fk_cuad_registros_diarios_jornal')
                ->onDelete('set null');

            // Tramo donde se pagó el bono
            $table->foreignId('tramo_pagado_bono_id')
                ->nullable()
                ->constrained('cuad_tramos_laborales', 'id', 'fk_cuad_registros_diarios_bono')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_registros_diarios');
    }
};
