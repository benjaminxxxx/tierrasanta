<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_detalles_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_reg_dia_id')->constrained('plan_registros_diarios')->cascadeOnDelete();
            $table->string('campo_nombre', 10);
            $table->integer('codigo_labor');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_detalles_horas');
    }
};
