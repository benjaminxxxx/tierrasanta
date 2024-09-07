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
        Schema::create('horas_acumuladas', function (Blueprint $table) {
            $table->id();
            $table->string('documento'); // DNI del trabajador
            $table->date('fecha_acumulacion'); // Fecha en la que se acumularon las horas
            $table->decimal('horas_acumuladas', 5, 2)->default(0.00); // Horas acumuladas
            $table->enum('estado', ['por_usar', 'usado'])->default('por_usar'); // Estado de la hora acumulada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horas_acumuladas');
    }
};
