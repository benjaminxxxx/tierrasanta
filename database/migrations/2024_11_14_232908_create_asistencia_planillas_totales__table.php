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
        Schema::create('asistencia_planillas_totales', function (Blueprint $table) {
            $table->id();
            $table->string('estado_asistencia',5);
            $table->unsignedBigInteger('reporte_diario_planilla_id');
            $table->integer('total');
            $table->timestamps();
            $table->foreign('reporte_diario_planilla_id')->references('id')->on('reporte_diario_campos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencia_planillas_totales');
    }
};
