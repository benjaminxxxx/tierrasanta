<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_acumulacion_usos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consolidado_destino_id');
            $table->unsignedBigInteger('consolidado_origen_id');
            $table->integer('minutos_consumidos');
            $table->timestamps();

            $table->unique(['consolidado_destino_id', 'consolidado_origen_id'], 'uq_destino_origen');

            $table->foreign('consolidado_destino_id', 'fk_destino_resumen')->references('id')->on('reg_resumen')->cascadeOnDelete();
            $table->foreign('consolidado_origen_id', 'fk_origen_resumen')->references('id')->on('reg_resumen')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_acumulacion_usos');
    }
};
