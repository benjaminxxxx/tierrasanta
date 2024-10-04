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
        Schema::create('tipo_asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 5)->unique(); // Código corto como 'A', 'F', etc.
            $table->string('descripcion'); // Descripción como "Asistido", "Falta", etc.
            $table->integer('horas_jornal')->default(0); 
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_asistencias');
    }
};
