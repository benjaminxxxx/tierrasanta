<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_actividades_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('campo', 20);
            $table->string('codigo_labor', 20);
            $table->string('nombre_labor', 200);
            $table->string('unidades', 10)->nullable();
            $table->unsignedInteger('recojos')->default(0);
            $table->unsignedSmallInteger('total_metodos')->default(0);
            $table->unsignedSmallInteger('total_planilla')->default(0);
            $table->unsignedSmallInteger('total_cuadrilla')->default(0);
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->timestamps();

            $table->unique(['fecha', 'campo', 'codigo_labor'], 'rep_act_dia_unique');
            $table->index(['fecha', 'campo']);
            $table->index(['fecha', 'codigo_labor']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_actividades_diarias');
    }
};
