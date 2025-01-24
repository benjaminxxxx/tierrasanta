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
        Schema::create('cuadrillero_actividad_recogidas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cuadrillero_actividad_id');
            $table->foreign('cuadrillero_actividad_id', 'fk_c_actividad_id')
                ->references('id')->on('cuadrillero_actividades')->onDelete('cascade');

            $table->unsignedBigInteger('recogida_id');
            $table->foreign('recogida_id', 'fk_recogida_id')
                ->references('id')->on('recogidas')->onDelete('cascade');

            $table->decimal('kg_logrados', 8, 2)->nullable();
            $table->decimal('bono', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrillero_actividad_recogidas');
    }
};
