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
        Schema::create('reporte_diario_cuadrilla_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporte_diario_id'); // Foreign key a reporte_diarios
            $table->string('campo', 10); // campo (varchar 10)
            $table->integer('labor'); // labor (int)
            $table->time('hora_inicio'); // hora de inicio (time)
            $table->time('hora_salida'); // hora de salida (time)
            $table->timestamps(); // timestamps para created_at y updated_at
    
            // Definir la relación con la tabla reporte_diarios
            $table->foreign('reporte_diario_id')
                  ->references('id')->on('reporte_diario_cuadrillas')
                  ->onDelete('cascade'); // Eliminar detalles si se elimina el reporte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_diario_cuadrilla_detalles');
    }
};
