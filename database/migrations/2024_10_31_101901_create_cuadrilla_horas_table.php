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
        Schema::create('cuadrilla_horas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cua_asi_sem_cua_id'); // Clave foránea hacia cua_asistencia_semanal_cuadrilleros->id
            $table->date('fecha');
            $table->decimal('horas', 4, 2);
            $table->decimal('costo_dia', 10, 2);
            $table->timestamps();

            // Relación con la tabla cua_asistencia_semanal_cuadrilleros
            $table->foreign('cua_asi_sem_cua_id')
                  ->references('id')
                  ->on('cua_asistencia_semanal_cuadrilleros')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_horas');
    }
};
