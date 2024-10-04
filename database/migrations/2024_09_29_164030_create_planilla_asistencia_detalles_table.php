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
        Schema::create('planilla_asistencia_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_asistencia_id')->constrained('planilla_asistencias')->onDelete('cascade');
            $table->date('fecha');
            $table->string('tipo_asistencia'); // Por ejemplo: "Presente", "Ausente", "Licencia"
            $table->decimal('horas_jornal', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_asistencia_detalles');
    }
};
