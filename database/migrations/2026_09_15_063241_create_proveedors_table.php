<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();

            // Enlace 1 a 1 único con la tabla personas
            $table->foreignId('persona_id')
                  ->unique()
                  ->constrained('personas')
                  ->onDelete('cascade');

            // ── Estado ante SUNAT ──────────────────────────────
            $table->string('tipo_contribuyente', 100)->nullable();
            $table->string('estado_contribuyente', 30)->nullable(); // ACTIVO / BAJA DE OFICIO / etc.
            $table->string('estado_domicilio', 30)->nullable();     // HABIDO / NO HABIDO / NO HALLADO
            $table->string('condicion', 30)->nullable();            // Condición adicional (ej: HABIDO)

            $table->date('fecha_inscripcion')->nullable();
            $table->date('fecha_inicio_actividades')->nullable();

            // ── Actividad económica ─────────────────────────────
            $table->string('ciiu', 20)->nullable();
            $table->string('actividad_comercio_exterior', 100)->nullable();

            // ── Flags fiscales comunes en Perú ───────────────
            $table->boolean('es_agente_retencion')->default(false);
            $table->boolean('es_buen_contribuyente')->default(false);

            // ── Control de verificación SUNAT ──────────────────
            $table->boolean('verificado')->default(false);
            $table->timestamp('verificado_at')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};