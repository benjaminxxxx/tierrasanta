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
        Schema::create('planilla_asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('grupo');
            $table->string('documento');
            $table->string('nombres');
            $table->decimal('total_horas', 8, 2);
            $table->unsignedTinyInteger('mes'); // Mes (1-12)
            $table->unsignedInteger('anio'); // Año
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_asistencias');
    }
};
