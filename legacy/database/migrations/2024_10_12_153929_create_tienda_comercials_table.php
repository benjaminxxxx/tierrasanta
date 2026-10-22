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
        Schema::create('tienda_comercials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            // Datos básicos
            $table->string('ruc', 11)->nullable()->unique();
            $table->string('contacto')->nullable();

            // Identidad legal (SUNAT)
            $table->string('razon_social')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('tipo_contribuyente', 100)->nullable();

            // Estado / condición ante SUNAT
            $table->string('condicion', 30)->nullable(); // "Sociedad Anonima Cerrada", etc.
            $table->string('estado_contribuyente', 30)->nullable(); // ACTIVO / BAJA DE OFICIO / etc.
            $table->string('estado_domicilio', 30)->nullable(); // HABIDO / NO HABIDO

            // Fechas
            $table->date('fecha_inscripcion')->nullable();
            $table->date('fecha_inicio_actividades')->nullable();

            // Ubicación
            $table->string('direccion_fiscal')->nullable();
            $table->string('distrito', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('departamento', 100)->nullable();

            // Actividad económica
            $table->string('ciiu', 20)->nullable();
            $table->string('actividad_comercio_exterior', 100)->nullable();

            // Control de verificación
            $table->boolean('verificado')->default(false);
            $table->timestamp('verificado_at')->nullable();

            // Auditoría
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('editado_por')->nullable();
            $table->unsignedBigInteger('eliminado_por')->nullable();

            $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
            $table->foreign('editado_por')->references('id')->on('users')->nullOnDelete();
            $table->foreign('eliminado_por')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
        ;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tienda_comercials');
    }
};
