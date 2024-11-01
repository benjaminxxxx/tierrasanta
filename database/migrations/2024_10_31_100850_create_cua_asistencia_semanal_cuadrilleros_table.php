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
        Schema::create('cua_asistencia_semanal_cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cua_id'); // Clave foránea hacia cuadrilleros
            $table->unsignedBigInteger('cua_asi_sem_gru_id'); // Clave foránea hacia cua_asistencia_semanal_grupos
            $table->decimal('monto_recaudado', 10, 2)->nullable();
            $table->timestamps();

            // Relación con la tabla cuadrilleros
            $table->foreign('cua_id')
                  ->references('id')
                  ->on('cuadrilleros')
                  ->onDelete('cascade');

            // Relación con la tabla cua_asistencia_semanal_grupos
            $table->foreign('cua_asi_sem_gru_id')
                  ->references('id')
                  ->on('cua_asistencia_semanal_grupos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cua_asistencia_semanal_cuadrilleros');
    }
};
