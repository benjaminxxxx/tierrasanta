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
        Schema::create('reporte_diario_cuadrillas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_cuadrilleros');
            $table->time('total_horas'); // total de horas (time)
            $table->date('fecha');
            $table->timestamps(); // timestamps para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_diario_cuadrillas');
    }
};
