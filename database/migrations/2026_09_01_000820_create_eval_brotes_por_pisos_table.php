<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_brotes_por_pisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->date('fecha')->nullable();
            $table->decimal('metros_cama_ha', 10, 3);
            $table->string('evaluador');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campania_id', 'fecha'], 'eval_brotes_campania_fecha_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_brotes_por_pisos');
    }
};
