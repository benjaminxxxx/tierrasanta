<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_resumen', function (Blueprint $table) {
            $table->id();
            $table->string('regador_documento');
            $table->string('regador_nombre');
            $table->boolean('no_acumular_horas')->default(false);
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->time('hora_inicio_almuerzo')->nullable();
            $table->time('hora_fin_almuerzo')->nullable();
            $table->time('total_horas_observaciones')->nullable();
            $table->unsignedSmallInteger('minutos_regados')->default(0);
            $table->unsignedSmallInteger('minutos_jornal')->default(0);
            $table->unsignedSmallInteger('minutos_acumulados')->default(0);
            $table->unsignedSmallInteger('minutos_utilizados')->default(0);
            $table->string('trabajador_type');
            $table->unsignedBigInteger('trabajador_id');
            $table->enum('estado', ['consolidado', 'noconsolidado'])->default('consolidado');
            $table->boolean('sincronizado')->default(false);
            $table->json('explicacion_jornal_computable')->nullable();
            $table->timestamps();

            $table->index(['regador_documento', 'fecha']);
            $table->index(['trabajador_type', 'trabajador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_resumen');
    }
};
