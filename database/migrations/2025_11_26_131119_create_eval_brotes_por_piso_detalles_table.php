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
        Schema::create('eval_brotes_por_piso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brotes_x_piso_id');

            $table->foreign('brotes_x_piso_id', 'fk_eval_brotes')
                ->references('id')
                ->on('eval_brotes_por_pisos')
                ->onDelete('cascade');

            $table->integer('numero_cama'); // N° DE CAMA MUESTREADA
            $table->decimal('longitud_cama', 8, 2)->nullable(); // LONGITUD CAMA (metros)

            $table->integer('brotes_aptos_2p_actual')->nullable(); // N° ACTUAL DE BROTES APTOS 2° PISO POR HECTÁREA
            $table->integer('brotes_aptos_2p_despues_n_dias')->nullable(); // N° DE BROTES APTOS 2° PISO DESPUÉS DE N DÍAS

            $table->integer('brotes_aptos_3p_actual')->nullable(); // N° ACTUAL DE BROTES APTOS 3° PISO
            $table->integer('brotes_aptos_3p_despues_n_dias')->nullable(); // N° DE BROTES APTOS 3° PISO DESPUÉS DE N DÍAS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eval_brotes_por_piso_detalles');
    }
};
