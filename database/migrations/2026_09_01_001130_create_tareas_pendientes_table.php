<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas_pendientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('clave')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tareas_pendientes')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('variante')->default('warning');
            $table->unsignedInteger('cantidad_afectados')->default(0);
            $table->string('estado')->default('pendiente');
            $table->string('servicio');
            $table->string('metodo_detectar')->nullable();
            $table->json('acciones')->nullable();
            $table->foreignId('ejecutado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ejecutado_en')->nullable();
            $table->timestamp('detectado_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_inicio', 'fecha_fin']);
            $table->index(['tipo', 'clave', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas_pendientes');
    }
};
