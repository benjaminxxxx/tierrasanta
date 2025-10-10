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
        Schema::create('plan_estados_asistencia', function (Blueprint $table) {
            $table->string('codigo', 5)->primary(); // Clave primaria, ej: 'A', 'F', 'P4'
            $table->string('descripcion'); // Ej: Asistió, Falta, Permiso, etc.
            $table->integer('horas_jornal')->default(0); // Horas a computar o pagar
            $table->string('color')->nullable(); // Color visual (para reportes o UI)
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_estados_asistencia');
    }
};
