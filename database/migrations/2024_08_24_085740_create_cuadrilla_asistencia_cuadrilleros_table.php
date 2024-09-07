<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuadrilla_asistencia_cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrilla_asistencia_id')
                  ->constrained('cuadrilla_asistencias')
                  ->onDelete('cascade')
                  ->name('fk_asistencia_id');  // Nombre más corto para la restricción
            $table->string('nombres');
            $table->string('identificador')->nullable();
            $table->string('dni')->nullable();
            $table->string('codigo_grupo');
            $table->boolean('planilla')->nullable()->default(false);
            
            $table->decimal('monto_recaudado', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_asistencia_cuadrilleros');
    }
};
