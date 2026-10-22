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
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('tipo_contribuyente', 100)->nullable();
            $table->string('estado_contribuyente', 30)->nullable();
            $table->string('estado_domicilio', 30)->nullable();
            $table->string('condicion', 30)->nullable();
            $table->date('fecha_inscripcion')->nullable();
            $table->date('fecha_inicio_actividades')->nullable();
            $table->string('ciiu', 20)->nullable();
            $table->string('actividad_comercio_exterior', 100)->nullable();
            $table->boolean('es_agente_retencion')->default(false);
            $table->boolean('es_buen_contribuyente')->default(false);
            $table->boolean('verificado')->default(false);
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('persona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
