<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campania_costos_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->decimal('area', 12, 4)->nullable();
            $table->decimal('tipo_cambio', 10, 4)->nullable();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->decimal('total_soles', 24, 4)->default(0.0000);
            $table->unsignedInteger('filas_origen')->default(0);
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['campo_campania_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_costos_produccion');
    }
};
