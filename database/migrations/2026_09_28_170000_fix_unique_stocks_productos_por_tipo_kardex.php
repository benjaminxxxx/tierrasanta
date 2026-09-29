<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * El stock es por producto + almacén + tipo de kardex (blanco/negro), pero el índice único
     * se quedó en (producto_id, almacen_id) cuando se agregó tipo_kardex. Resultado: un producto
     * solo podía tener stock de un tipo y crear el del otro fallaba con "Duplicate entry".
     */
    public function up(): void
    {
        Schema::table('stocks_productos', function (Blueprint $table) {
            // Primero el nuevo: también sirve de índice para la FK de producto_id
            $table->unique(['producto_id', 'almacen_id', 'tipo_kardex'], 'stocks_productos_producto_almacen_tipo_unique');
        });

        Schema::table('stocks_productos', function (Blueprint $table) {
            $table->dropUnique('stocks_productos_producto_id_almacen_id_unique');
        });
    }

    public function down(): void
    {
        // Solo es reversible si no hay productos con stock en ambos tipos
        Schema::table('stocks_productos', function (Blueprint $table) {
            $table->unique(['producto_id', 'almacen_id']);
        });

        Schema::table('stocks_productos', function (Blueprint $table) {
            $table->dropUnique('stocks_productos_producto_almacen_tipo_unique');
        });
    }
};
