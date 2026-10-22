<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyeccion_rendimiento_podas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campo_campania_id');
            $table->unsignedInteger('nro_muestra')->nullable();
            $table->decimal('peso_fresco_kg', 8, 2)->nullable();
            $table->decimal('peso_seco_kg', 8, 2)->nullable();
            $table->decimal('rdto_hectarea_kg', 10, 2)->nullable();
            $table->decimal('relacion_fresco_seco', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['campo_campania_id', 'nro_muestra'], 'uniq_poda_muestra');

            $table->foreign('campo_campania_id', 'fk_poda_campania')->references('id')->on('campos_campanias')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyeccion_rendimiento_podas');
    }
};
