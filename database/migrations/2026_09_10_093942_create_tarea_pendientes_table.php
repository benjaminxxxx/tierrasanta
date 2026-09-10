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

            // Identidad de la tarea: el mismo 'tipo' agrupa una familia de tareas
            // (ej. 'riego-desincronizado'); 'clave' discrimina instancias dentro
            // de ese tipo (ej. '2026-08' para la subtarea de un mes específico,
            // null para la tarea "resumen" general).
            $table->string('tipo');
            $table->string('clave')->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('tareas_pendientes')->nullOnDelete();

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('variante')->default('warning'); // info|warning|danger|success

            $table->unsignedInteger('cantidad_afectados')->default(0);
            $table->string('estado')->default('pendiente'); // pendiente|completada

            // Contrato de ejecución: el componente genérico resuelve estos campos
            // en vez de saber nada del dominio.
            $table->string('servicio'); // FQCN, ej. App\Services\Riego\VerificacionSincronizacionRiegoServicio
            $table->string('metodo_detectar')->nullable(); // recuenta esta tarea (sin params)
            $table->json('acciones')->nullable(); // [{titulo, metodo, parametros}, ...] — 1 o 2 botones

            $table->foreignId('ejecutado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ejecutado_en')->nullable();
            $table->timestamp('detectado_en')->nullable();

            $table->timestamps();

            $table->index(['tipo', 'clave', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas_pendientes');
    }
};