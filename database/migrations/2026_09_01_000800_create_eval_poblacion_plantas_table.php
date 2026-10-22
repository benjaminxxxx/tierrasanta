<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_poblacion_plantas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_siembra')->nullable();
            $table->decimal('area_lote', 10, 4);
            $table->string('evaluador')->nullable();
            $table->decimal('metros_cama_ha', 10, 3)->nullable();
            $table->foreignId('campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->date('fecha_eval_cero');
            $table->date('fecha_eval_resiembra')->nullable();
            $table->timestamps();

            $table->unique('campania_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_poblacion_plantas');
    }
};
