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
        Schema::create('cuad_registros_diarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuadrillero_id')
                  ->constrained('cuadrilleros')
                  ->restrictOnDelete();

            $table->date('fecha');
            $table->decimal('costo_personalizado_dia', 10, 2)->nullable();
            $table->boolean('asistencia')->default(true);
            $table->decimal('total_bono', 10, 2)->default(0);
            $table->decimal('costo_dia', 10, 2)->default(0);

            //agregados despues
            $table->decimal('total_horas', 5, 2)->nullable();
            $table->boolean('esta_pagado')->default(false);
            $table->boolean('bono_esta_pagado')->default(false); 
            $table->string('codigo_grupo');

            // 2. Agrega la restricción de llave foránea.
            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('restrict');

            //mas adelante estan
            //tramo_pagado_jornal_id,tramo_pagado_bono_id

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_registro_diarios');
    }
};
