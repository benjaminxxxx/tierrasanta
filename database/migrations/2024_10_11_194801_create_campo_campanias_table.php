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
        Schema::create('campo_campanias', function (Blueprint $table) {
            $table->id();
            $table->string('lote'); // Campo para el lote
            $table->decimal('area', 8, 3)->nullable(); // Campo para el área, formato 3.134
            $table->string('campania')->nullable(); // Campo para la campaña, e.g., T.2024 o N2.2024
            $table->date('fecha_vigencia'); // Campo para la fecha de vigencia
                     
            $table->unique(['lote', 'fecha_vigencia']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campo_campanias');
    }
};
