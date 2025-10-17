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
        Schema::create('plan_resumen_diario', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); // Campo para la fecha
            $table->integer('total_actividades')->default(1);
            $table->integer('total_cuadrillas')->default(0);
            $table->integer('total_planilla')->default(0);
            $table->json('resumen_cuadrilla')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_resumen_diario');
    }
};
