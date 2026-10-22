<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->enum('direccion', ['entrada', 'salida']);
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 12, 4);
            $table->decimal('costo_unitario', 18, 6)->nullable();
            $table->decimal('costo_total', 18, 4)->nullable();
            $table->date('fecha_movimiento');
            $table->string('motivo', 150)->nullable();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->string('origen_type')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->enum('tipo_kardex', ['blanco', 'negro']);
            $table->timestamps();

            $table->index(['producto_id', 'almacen_id', 'fecha_movimiento', 'tipo_kardex'], 'idx_kardex_busqueda');
            $table->index(['origen_type', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
