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
        Schema::create('campos_activos', function (Blueprint $table) {
            $table->id();
            $table->string('campo_nombre'); // Relación con la tabla 'campos'
            $table->integer('mes'); // Número de mes (1-12)
            $table->integer('anio'); // Año de la asignación
            $table->timestamps();
        
            $table->foreign('campo_nombre')->references('nombre')->on('campos')->onDelete('cascade');
            $table->unique(['campo_nombre', 'mes', 'anio']); // Evita duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_activos');
    }
};
