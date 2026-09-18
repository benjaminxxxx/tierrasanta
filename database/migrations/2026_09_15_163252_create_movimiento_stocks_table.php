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
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();

            $table->enum('direccion', ['entrada', 'salida']);
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 12, 4);
            $table->date('fecha_movimiento');
            $table->string('motivo', 150)->nullable();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->nullableMorphs('origen');
            $table->enum('tipo_kardex', ['blanco', 'negro']);

            $table->timestamps();

            // Índice compuesto optimizado
            $table->index(['producto_id', 'almacen_id', 'fecha_movimiento', 'tipo_kardex'], 'idx_kardex_busqueda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
