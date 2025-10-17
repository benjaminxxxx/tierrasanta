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
        Schema::create('reg_horas_acumuladas', function (Blueprint $table) {
            $table->id();
            $table->string('documento'); // DNI del trabajador
            $table->date('fecha_acumulacion'); // Fecha en la que se acumularon las horas
            $table->date('fecha_uso')->nullable();
            $table->integer('minutos_acomulados'); // Minutos acumuladas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_horas_acumuladas');
    }
};
