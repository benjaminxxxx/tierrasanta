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
        //antes reporte_diario_detalles
        //reporte_diarios pasara a plan_registros_diarios
        Schema::create('plan_detalles_horas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporte_diario_id');
            $table->string('campo', 10); // campo (varchar 10)
            $table->integer('labor'); // labor (int)
            $table->time('hora_inicio'); // hora de inicio (time)
            $table->time('hora_salida'); // hora de salida (time)
            $table->timestamps(); // timestamps para created_at y updated_at
            
            $table->foreign('reporte_diario_id')
                  ->references('id')->on('plan_registros_diarios')
                  ->onDelete('cascade'); // Eliminar detalles si se elimina el reporte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_detalles_horas');
    }
};
