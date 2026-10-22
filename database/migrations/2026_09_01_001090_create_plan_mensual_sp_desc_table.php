<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_mensual_sp_desc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_mensual_id')->constrained('plan_mensuales')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('referencia');
            $table->string('tipo')->nullable();
            $table->decimal('comision', 6, 2)->default(0.00);
            $table->decimal('prima_seguros', 6, 2)->default(0.00);
            $table->decimal('aporte_obligatorio', 6, 2)->default(0.00);
            $table->decimal('porcentaje', 6, 2)->default(0.00);
            $table->decimal('porcentaje_65', 6, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['plan_mensual_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mensual_sp_desc');
    }
};
