<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Cabecera del servicio externo (comprobante).
     * - unidad: texto libre (hora, kilo, tonelada...) para no depender de una tabla de tipos.
     * - costo_unitario: en factura es SIN IGV; en boleta/nota es el monto pagado (con IGV).
     * - costo_total: suma de los costos de los detalles = total pagado (el IGV es gasto, no hay crédito fiscal).
     */
    public function up(): void
    {
        Schema::create('servicios_campo', function (Blueprint $table) {
            $table->id();
            $table->string('servicio');
            $table->string('unidad', 50);
            $table->decimal('costo_unitario', 14, 6);
            $table->string('tipo_comprobante', 30); // factura, boleta, nota_venta
            $table->string('numero_comprobante', 50)->nullable();
            $table->string('tipo_costo', 10); // blanco, negro
            $table->date('fecha_comprobante');
            $table->decimal('porcentaje_igv', 5, 2)->default(18);
            $table->decimal('cantidad_total', 12, 3)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha_comprobante');
            $table->index('servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_campo');
    }
};
