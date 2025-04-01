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
        Schema::create('poblacion_plantas_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poblacion_plantas_id');
            $table->integer('cama_muestreada');
            $table->decimal('longitud_cama', 8, 2);
            $table->integer('plantas_x_cama');
            $table->decimal('plantas_x_metro', 5, 2);
            $table->timestamps();

            // Clave foránea
            $table->foreign('poblacion_plantas_id')->references('id')->on('poblacion_plantas')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poblacion_plantas_detalles');
    }
};
