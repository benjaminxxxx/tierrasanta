<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('presentacion_id')->nullable()->constrained('presentaciones')->nullOnDelete();
            $table->string('nombre_presentacion')->nullable();
            $table->decimal('cantidad', 12, 4);
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0.00);
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);
            $table->decimal('total_linea', 12, 4)->default(0.0000);
            $table->decimal('factor_conversion_usado', 12, 4)->default(1.0000);
            $table->decimal('cantidad_base', 12, 4);
            $table->decimal('costo_unitario_base', 18, 6)->default(0.000000);
            $table->decimal('costo_total_kardex', 12, 4)->default(0.0000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};
