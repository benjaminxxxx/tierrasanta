<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de unidades de producción (KG, LAVADOR, CAJA…). Cada labor que produce algo tiene una (labores.unidades
 * guarda su código; si la labor no produce, va vacía). Se carga con el Excel de recursos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_unidades', function (Blueprint $table) {
            $table->string('codigo', 20);
            $table->string('nombre');
            // Decimales con que se registra (kg: 2; lavadores, cajas: 0)
            $table->unsignedTinyInteger('decimales')->default(2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_unidades');
    }
};
