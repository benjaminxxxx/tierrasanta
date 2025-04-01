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
        Schema::create('kardex_productos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kardex_id'); // Relación con el kardex principal
            $table->unsignedBigInteger('producto_id'); // Producto asociado
            $table->decimal('stock_inicial', 18, 3); // Stock inicial al abrir el kardex
            $table->decimal('costo_unitario', 18, 13); // Costo promedio inicial
            $table->decimal('costo_total', 18, 13);
            $table->decimal('stock_final', 18, 3)->nullable(); // Stock final al cerrar
            $table->decimal('costo_final', 18, 13)->nullable(); // Costo promedio final al cerrar
            $table->enum('estado', ['activo', 'cerrado'])->default('activo'); // Estado del kardex del producto
            $table->enum('metodo_valuacion', ['promedio', 'peps'])->default('promedio');
            $table->string('file',255)->nullable();
            $table->enum('tipo_kardex', ['blanco','negro'])->nullable();
            $table->timestamps(); // created_at y updated_at

            // Claves foráneas
            $table->foreign('kardex_id')->references('id')->on('kardex')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_productos');
    }
};
