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
        Schema::create('estadistica_mensuales', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('mes');   // 1-12
            $table->unsignedSmallInteger('anio'); // 2026

            $table->string('clave', 60);          // 'total_empleados', 'contratados_mes', 'tasa_rotacion'
            $table->decimal('valor', 12, 4);      // el número calculado
            $table->decimal('valor_anterior', 12, 4)->nullable(); // para el "vs mes anterior"
            $table->timestamps();
            $table->unique(['mes', 'anio', 'clave']); // no duplicar
            $table->index(['mes', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estadistica_mensuales');
    }
};
