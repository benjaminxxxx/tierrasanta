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
        Schema::create('almacen_producto_salidas', function (Blueprint $table) {
            $table->id(); // ID único del registro
            $table->integer('item')->nullable(); // Número de item
            $table->unsignedBigInteger('producto_id'); // ID del producto
            $table->string('campo_nombre'); // Nombre del campo o lugar donde se utiliza
            $table->decimal('cantidad', 10, 3)->nullable(); // Cantidad decimal (hasta 3 decimales)
            $table->date('fecha_reporte'); // Fecha del reporte
            $table->decimal('costo_por_kg', 10, 2)->nullable(); // Costo por kilogramo (o unidad de medida)
            $table->decimal('total_costo', 10, 2)->nullable(); // Total calculado (cantidad * costo_por_kg)

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreignId('maquinaria_id')->nullable()
                ->constrained('maquinarias')
                ->onDelete('set null');
            $table->unsignedBigInteger('cantidad_kardex_producto_id')->nullable();
            $table->unsignedBigInteger('kardex_producto_id')->nullable();
            $table->decimal('cantidad_stock_inicial', 8, 3)->nullable();
            $table->foreign('cantidad_kardex_producto_id')
                ->references('id')
                ->on('kardex_productos')
                ->onDelete('set null');

            $table->foreign('kardex_producto_id')
                ->references('id')
                ->on('kardex_productos')
                ->onDelete('set null');

            $table->integer('indice')->nullable();
            $table->string('tipo_kardex', 20)->nullable();
            $table->string('registro_carga', 20)->nullable();
            $table->timestamps();
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
