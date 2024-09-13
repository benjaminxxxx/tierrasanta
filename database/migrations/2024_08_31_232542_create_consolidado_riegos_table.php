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
        Schema::create('consolidado_riegos', function (Blueprint $table) {
            $table->id();
            $table->string('regador_documento');
            $table->string('regador_nombre');
            $table->integer('descuento_horas_almuerzo')->default(0);
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->time('total_horas_riego');
            $table->time('total_horas_jornal');
            $table->time('total_horas_observaciones')->nullable(); 
            $table->time('total_horas_acumuladas')->nullable(); 
            $table->enum('estado',['consolidado','noconsolidado'])->default('consolidado');
            $table->timestamps();
            $table->index(['regador_documento', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidado_riegos');
    }
};
