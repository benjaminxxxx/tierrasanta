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
        Schema::create('plan_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->foreignId('codigo_tipo')->constrained('plan_tipo_asistencias')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->integer('dias')->default(1);
            $table->string('motivo')->nullable();
            $table->string('documento_adjunto')->nullable();
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_permisos');
    }
};
