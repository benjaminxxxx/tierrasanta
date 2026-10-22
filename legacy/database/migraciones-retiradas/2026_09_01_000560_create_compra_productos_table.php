<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('tienda_comercial_id')->nullable()->constrained('tienda_comercials')->nullOnDelete();
            $table->date('fecha_compra');
            $table->string('orden_compra')->nullable();
            $table->decimal('total', 10, 2)->default(0.00);
            $table->decimal('stock', 10, 3)->default(0.000);
            $table->date('fecha_termino')->nullable();
            $table->string('tipo_compra_codigo', 4)->nullable();
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->string('tabla12_tipo_operacion')->nullable();
            $table->enum('tipo_kardex', ['blanco', 'negro']);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tipo_compra_codigo')->references('codigo')->on('sunat_tabla10_tipo_comprobantes_pago')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_productos');
    }
};
