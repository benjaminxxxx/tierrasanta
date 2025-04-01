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
        Schema::create('compra_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id'); // Relación con productos
            $table->unsignedBigInteger('tienda_comercial_id')->nullable(); // Clave foránea a tienda_comercial, nullable
            $table->date('fecha_compra'); // Fecha de la compra
            $table->string('orden_compra')->nullable(); // Orden de compra, nullable
            $table->string('factura')->nullable(); // Factura, nullable
            $table->decimal('costo_por_kg', 10, 2); // Costo por unidad de medida
            $table->boolean('estado')->default(true); // Estado de la compra
            $table->decimal('total', 10, 2)->default(0); // Reemplaza 'existing_column' por el nombre de una columna existente si quieres un orden específico
            $table->decimal('stock', 10, 3)->default(0);
            $table->date('fecha_termino')->nullable();
            $table->timestamps();

            // Claves foráneas
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('tienda_comercial_id')->references('id')->on('tienda_comercials')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_productos');
    }
};
