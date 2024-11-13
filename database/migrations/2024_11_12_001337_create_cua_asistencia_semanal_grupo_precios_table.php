<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cua_asistencia_semanal_grupo_precios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cua_asistencia_semanal_grupo_id');
            $table->unsignedBigInteger('cua_asi_sem_id');
            $table->string('gru_cua_cod');
            $table->decimal('costo_dia', 10, 2)->nullable();
            $table->decimal('costo_hora', 10, 3)->nullable();
            $table->date('fecha');
            $table->unsignedBigInteger('cua_asi_sem_cua_id')->nullable();
            $table->timestamps();

            // Definición de las llaves foráneas con nombres personalizados
            $table->foreign('cua_asistencia_semanal_grupo_id', 'fk_asi_grupo')
                ->references('id')
                ->on('cua_asistencia_semanal_grupos')
                ->onDelete('cascade');

            $table->foreign('cua_asi_sem_id', 'fk_asi_semanal')
                ->references('id')
                ->on('cua_asistencia_semanal')
                ->onDelete('cascade');

            $table->foreign('gru_cua_cod', 'fk_grupo_cod')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('cascade');

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
        Schema::dropIfExists('cua_asistencia_semanal_grupo_precios');
    }
};
