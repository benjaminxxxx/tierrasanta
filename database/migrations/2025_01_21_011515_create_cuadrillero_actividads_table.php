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
        Schema::create('cuadrillero_actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cua_asi_sem_cua_id');
            $table->foreign('cua_asi_sem_cua_id')
                  ->references('id')
                  ->on('cua_asistencia_semanal_cuadrilleros')
                  ->onDelete('cascade');
            
            $table->unsignedBigInteger('actividad_id');
            $table->foreign('actividad_id', 'fk_actividad_id')
                ->references('id')->on('actividades')->onDelete('cascade');

            
            $table->decimal('total_bono', 8, 2)->nullable();
            $table->decimal('total_costo', 8, 2)->nullable();

            $table->timestamps();
            
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrillero_actividades');
    }
};
