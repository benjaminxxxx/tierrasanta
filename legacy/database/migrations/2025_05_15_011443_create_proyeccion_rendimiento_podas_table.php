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
         Schema::create('proyeccion_rendimiento_podas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campo_campania_id');
            $table->unsignedInteger('nro_muestra')->nullable(); // N° DE MUESTRA
            $table->decimal('peso_fresco_kg', 8, 2)->nullable(); // PESO FRESCO (kg)
            $table->decimal('peso_seco_kg', 8, 2)->nullable();   // PESO SECO (kg)
            $table->decimal('rdto_hectarea_kg', 10, 2)->nullable(); // RDTO/HECTAREA (kg)
            $table->decimal('relacion_fresco_seco', 8, 4)->nullable(); // RELACIÓN FRESCO/SECO

            // Clave foránea con nombre explícito más corto
            $table->foreign('campo_campania_id', 'fk_poda_campania')
                  ->references('id')
                  ->on('campos_campanias')
                  ->onDelete('cascade');

            $table->timestamps();

            // Índice único con nombre explícito más corto
            $table->unique(['campo_campania_id', 'nro_muestra'], 'uniq_poda_muestra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('proyeccion_rendimiento_podas', function (Blueprint $table) {
            $table->dropForeign('fk_poda_campania');
            $table->dropUnique('uniq_poda_muestra');
        });

        Schema::dropIfExists('proyeccion_rendimiento_podas');
    }
};
