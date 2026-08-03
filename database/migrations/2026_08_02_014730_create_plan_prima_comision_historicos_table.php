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
        Schema::create('plan_prima_comision_historicos', function (Blueprint $table) {
            $table->id();
            $table->string('referencia'); // HABITAT, INTEGRA, PRIMA, PROFUTURO
            $table->decimal('comision_flujo', 6, 2)->nullable();
            $table->decimal('comision_saldo', 6, 2)->nullable();
            $table->decimal('prima_seguros', 6, 2)->nullable();
            $table->decimal('aporte_obligatorio', 6, 2)->nullable();
            $table->decimal('remuneracion_maxima_asegurable', 10, 2)->nullable();
            $table->date('fecha_inicio');
            $table->timestamps();

            $table->unique(['referencia', 'fecha_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prima_comision_historicos');
    }
};
