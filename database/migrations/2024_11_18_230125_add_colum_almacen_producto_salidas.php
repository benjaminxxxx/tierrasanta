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
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            // Agregar columnas
            $table->unsignedBigInteger('cantidad_kardex_producto_id')->nullable();
            $table->unsignedBigInteger('kardex_producto_id')->nullable();
            $table->decimal('cantidad_stock_inicial', 8, 3)->nullable();

            // Agregar claves foráneas
            $table->foreign('cantidad_kardex_producto_id')
                ->references('id')
                ->on('kardex_productos')
                ->onDelete('set null');

            $table->foreign('kardex_producto_id')
                ->references('id')
                ->on('kardex_productos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('almacen_producto_salidas', function (Blueprint $table) {
                // Eliminar claves foráneas
                $table->dropForeign(['cantidad_kardex_producto_id']);
                $table->dropForeign(['kardex_producto_id']);
    
                // Eliminar columnas
                $table->dropColumn(['cantidad_kardex_producto_id', 'kardex_producto_id', 'cantidad_stock_inicial']);
            });
    }
};
