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
        Schema::create('almacen_producto_salidas', function (Blueprint $table) {
            $table->id(); // ID único del registro
            $table->integer('item')->nullable(); // Número de item
            $table->unsignedBigInteger('producto_id'); // ID del producto
            $table->string('campo_nombre'); // Nombre del campo o lugar donde se utiliza
            $table->decimal('cantidad', 10, 3)->nullable(); // Cantidad decimal (hasta 3 decimales)
            $table->date('fecha_reporte'); // Fecha del reporte
            $table->unsignedBigInteger('compra_producto_id')->nullable(); // Relación con la tabla compras
            $table->decimal('costo_por_kg', 10, 2)->nullable(); // Costo por kilogramo (o unidad de medida)
            $table->decimal('total_costo', 10, 2)->nullable(); // Total calculado (cantidad * costo_por_kg)
            $table->timestamps();

            // Claves foráneas
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('compra_producto_id')->references('id')->on('compra_productos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacen_producto_salidas');
    }
};
