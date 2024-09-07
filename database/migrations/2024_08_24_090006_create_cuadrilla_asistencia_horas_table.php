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
        Schema::create('cuadrilla_asistencia_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrillero_id')->constrained('cuadrilla_asistencia_cuadrilleros')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('horas_trabajadas',10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_asistencia_horas');
    }
};
