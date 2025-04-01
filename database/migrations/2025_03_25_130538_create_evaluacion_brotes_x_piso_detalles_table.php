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
        Schema::create('evaluacion_brotes_x_piso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brotes_x_piso_id');
            $table->foreign('brotes_x_piso_id')->references('id')->on('evaluacion_brotes_x_pisos')->onDelete('cascade');
        
            $table->integer('numero_cama_muestreada'); // N° DE CAMA MUESTREADA
            $table->decimal('longitud_cama', 8, 2)->nullable(); // LONGITUD CAMA (metros)
            
            $table->integer('brotes_aptos_2p_actual')->nullable(); // N° ACTUAL DE BROTES APTOS 2° PISO POR HECTÁREA
            $table->integer('brotes_aptos_2p_despues_n_dias')->nullable(); // N° DE BROTES APTOS 2° PISO DESPUÉS DE N DÍAS
        
            $table->integer('brotes_aptos_3p_actual')->nullable(); // N° ACTUAL DE BROTES APTOS 3° PISO
            $table->integer('brotes_aptos_3p_despues_n_dias')->nullable(); // N° DE BROTES APTOS 3° PISO DESPUÉS DE N DÍAS

            // Nuevos campos calculados
            $table->decimal('brotes_aptos_2p_actual_calculado', 12, 2)->nullable();
            $table->decimal('brotes_aptos_2p_despues_n_dias_calculado', 12, 2)->nullable();
            $table->decimal('brotes_aptos_3p_actual_calculado', 12, 2)->nullable();
            $table->decimal('brotes_aptos_3p_despues_n_dias_calculado', 12, 2)->nullable();
            $table->decimal('total_actual_de_brotes_aptos_23_piso_calculado', 12, 2)->nullable();
            $table->decimal('total_de_brotes_aptos_23_pisos_despues_n_dias_calculado', 12, 2)->nullable();
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_brotes_x_piso_detalles');
    }
};
