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
        Schema::create('evaluacion_infestaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');            
            $table->unsignedBigInteger('campo_campania_id');
            $table->timestamps();
            $table->foreign('campo_campania_id')->references('id')->on('campos_campanias');

            // Índice único combinado
            $table->unique(['campo_campania_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_infestaciones');
    }
};
