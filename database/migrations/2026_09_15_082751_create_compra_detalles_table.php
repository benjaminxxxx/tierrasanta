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
      Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            
            // Relación opcional/histórica a la presentación usada
            $table->foreignId('presentacion_id')->nullable()->constrained('presentaciones')->nullOnDelete();

            // Snapshot del nombre del empaque al momento de la compra
            $table->string('nombre_presentacion')->nullable(); 

            // Datos digitados en la compra según la presentación seleccionada
            $table->decimal('cantidad', 12, 4); // Ej: 2 Cajas
            $table->decimal('costo_unitario', 12, 4); // Ej: S/ 150.00 por caja
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);
            $table->decimal('total_linea', 12, 4)->default(0);

            // 🔹 PROTECCIÓN HISTÓRICA Y KARDEX (Snapshots inmutables convertidos a unidad base)
            $table->decimal('factor_conversion_usado', 12, 4)->default(1.0000); // Copia congelada del factor
            $table->decimal('cantidad_base', 12, 4); // (cantidad * factor_conversion_usado) -> Va al Kardex
            $table->decimal('costo_unitario_base', 18, 6)->default(0); // (costo_unitario / factor_conversion_usado)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};
