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
        Schema::create('kardex', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 255);
            $table->date('fecha_inicial'); // Fecha de apertura
            $table->date('fecha_final')->nullable(); // Fecha de cierre
            $table->enum('estado', ['activo', 'cerrado'])->default('activo'); // Estado general del kardex
            $table->boolean('eliminado')->default(false); // Estado general del kardex
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};
