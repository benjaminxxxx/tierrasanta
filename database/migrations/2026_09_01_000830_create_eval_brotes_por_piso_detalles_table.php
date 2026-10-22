<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_brotes_por_piso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brotes_x_piso_id');
            $table->integer('numero_cama');
            $table->decimal('longitud_cama', 8, 2)->nullable();
            $table->integer('brotes_aptos_2p_actual')->nullable();
            $table->integer('brotes_aptos_2p_despues_n_dias')->nullable();
            $table->integer('brotes_aptos_3p_actual')->nullable();
            $table->integer('brotes_aptos_3p_despues_n_dias')->nullable();
            $table->timestamps();

            $table->foreign('brotes_x_piso_id', 'fk_eval_brotes')->references('id')->on('eval_brotes_por_pisos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_brotes_por_piso_detalles');
    }
};
