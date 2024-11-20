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
        Schema::table('productos', function (Blueprint $table) {
            // Agregar los campos como nullable
            $table->string('codigo_tipo_existencia', 4)->nullable(); // Ajusta el campo 'after' según la posición que quieras
            $table->string('codigo_unidad_medida', 4)->nullable(); // Ajusta el campo 'after' según la posición que quieras
            $table->string('codigo_existencia')->nullable();
            // Establecer las claves foráneas
            $table->foreign('codigo_tipo_existencia')->references('codigo')->on('sunat_tabla5_tipo_existencias')->onDelete('set null');
            $table->foreign('codigo_unidad_medida')->references('codigo')->on('sunat_tabla6_codigo_unidad_medida')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['codigo_tipo_existencia']);
            $table->dropForeign(['codigo_unidad_medida']);
            
            // Eliminar los campos
            $table->dropColumn(['codigo_tipo_existencia', 'codigo_unidad_medida','codigo_existencia']);
        });
    }
};
