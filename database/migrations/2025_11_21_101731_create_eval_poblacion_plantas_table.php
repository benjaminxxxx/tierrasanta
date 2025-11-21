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
        Schema::create('eval_poblacion_plantas', function (Blueprint $table) {
            $table->id();

            // Datos generales
            $table->date('fecha_siembra')->nullable();
            $table->decimal('area_lote', 10, 4);

            $table->string('evaluador', 255)->nullable();

            $table->decimal('metros_cama_ha', 10, 3)->nullable(); // 5000 por ejemplo

            // Relación con campaña (una evaluación por campaña)
            $table->unsignedBigInteger('campania_id');
            $table->foreign('campania_id')
                ->references('id')
                ->on('campos_campanias')
                ->onDelete('restrict');

            // Fechas de evaluación
            $table->date('fecha_eval_cero');
            $table->date('fecha_eval_resiembra')->nullable(); // Puede no existir resiembra

            $table->timestamps();

            // Solo una evaluación por campaña
            $table->unique('campania_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eval_poblacion_plantas');
    }
};
