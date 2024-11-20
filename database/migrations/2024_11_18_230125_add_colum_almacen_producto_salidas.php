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
            $table->unsignedBigInteger('cantidad_kardex_producto_id')->nullable();
            $table->decimal('cantidad_stock_inicial',8,2)->nullable();
            $table->foreign('cantidad_kardex_producto_id')->references('id')->on('kardex_productos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->dropForeign(['cantidad_kardex_producto_id']);
            $table->dropColumn(['cantidad_kardex_producto_id','cantidad_stock_inicial']);
        });
    }
};
