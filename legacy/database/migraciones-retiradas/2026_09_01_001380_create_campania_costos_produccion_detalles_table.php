<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campania_costos_produccion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costo_produccion_id')->constrained('campania_costos_produccion')->cascadeOnDelete();
            $table->unsignedInteger('orden');
            $table->string('seccion', 40);
            $table->string('grupo', 150)->nullable();
            $table->string('item');
            $table->string('codigo', 40)->nullable();
            $table->decimal('cantidad', 18, 4)->nullable();
            $table->decimal('costo_soles', 24, 4)->default(0.0000);

            $table->index(['costo_produccion_id', 'orden'], 'ccp_detalles_produccion_orden_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_costos_produccion_detalles');
    }
};
