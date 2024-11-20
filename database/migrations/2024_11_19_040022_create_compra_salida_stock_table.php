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
        Schema::create('compra_salida_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kardex_producto_id');
            $table->unsignedBigInteger('compra_producto_id');
            $table->unsignedBigInteger('salida_almacen_id');
            $table->decimal('stock',8,2);
            
            $table->foreign('salida_almacen_id')->references('id')->on('almacen_producto_salidas')->onDelete('cascade');
            $table->foreign('compra_producto_id')->references('id')->on('compra_productos')->onDelete('cascade');
            $table->foreign('kardex_producto_id')->references('id')->on('kardex_productos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_salida_stock');
    }
};
