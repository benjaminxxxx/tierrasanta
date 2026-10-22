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
        Schema::create('actividad_metodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();

            $table->string('titulo'); // "Método x Jornal #1", "Método Destajo Especial", etc.
            $table->decimal('estandar', 10, 2)->nullable(); // null = modo destajo, valor = modo sobreestandar

            $table->unsignedTinyInteger('orden')->default(1); // para mostrar en orden
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad_metodos');
    }
};
