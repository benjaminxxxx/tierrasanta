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
        Schema::create('kardex_consolidados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kardex_id');
            $table->string('codigo_existencia')->nullable(); // Código del producto
            $table->integer('producto_id')->nullable();
            $table->string('producto_nombre');
            $table->enum('tipo_kardex', ['blanco', 'negro']);
            $table->string('categoria_producto')->nullable();
            $table->string('condicion')->nullable(); // Ej: nuevo, usado
            $table->string('unidad_medida')->nullable(); // Clave foránea a tabla6 (SUNAT)
            $table->decimal('total_entradas_unidades', 15, 4)->nullable();
            $table->decimal('total_entradas_importe', 15, 2)->nullable();
            $table->decimal('total_salidas_unidades', 15, 4)->nullable();
            $table->decimal('total_salidas_importe', 15, 2)->nullable();
            $table->decimal('saldo_unidades', 15, 4)->nullable();
            $table->decimal('saldo_importe', 15, 2)->nullable();
            $table->timestamps();
            $table->foreign('kardex_id')->references('id')->on('kardex')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_consolidados');
    }
};
