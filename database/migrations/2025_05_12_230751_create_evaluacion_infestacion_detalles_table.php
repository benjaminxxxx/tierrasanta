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
        Schema::create('evaluacion_infestacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluacion_infestacion_id');
            $table->integer('numero_penca');
            $table->integer('piso_2')->nullable();
            $table->integer('piso_3')->nullable();
            $table->timestamps();

            $table->foreign('evaluacion_infestacion_id', 'evalinf_detalle_fk')
                ->references('id')
                ->on('evaluacion_infestaciones')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_infestacion_detalles');
    }
};
