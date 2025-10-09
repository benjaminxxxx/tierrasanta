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
            $table->string('tipo_compra_codigo', 4)->nullable();
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->string('tabla12_tipo_operacion')->nullable();
            $table->enum('tipo_kardex', ['blanco', 'negro']); // Blanco: Facturas, Negro: Boletas
            // Establecer las claves foráneas
            $table->foreign('tipo_compra_codigo')->references('codigo')->on('sunat_tabla10_tipo_comprobantes_pago')->onDelete('set null');

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
