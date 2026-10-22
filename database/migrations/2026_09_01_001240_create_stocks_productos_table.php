<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->decimal('cantidad', 12, 4)->default(0.0000);
            $table->enum('tipo_kardex', ['blanco', 'negro']);
            $table->timestamps();

            $table->unique(['producto_id', 'almacen_id', 'tipo_kardex'], 'stocks_productos_producto_almacen_tipo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks_productos');
    }
};
