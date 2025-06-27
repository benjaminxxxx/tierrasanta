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
        Schema::create('venta_facturada_cochinillas', function (Blueprint $table) {
            $table->id();

            // === DATOS DEL ORIGEN ===
            $table->date('fecha_ingreso')->nullable();             // Fecha de ingreso de la cochinilla
            $table->string('campania')->nullable();                // Ej: 2025-C1
            $table->string('campo')->nullable();                   // Campo de origen (ej: A3)
            $table->decimal('area', 8, 3)->nullable();             // Área en hectáreas
            $table->string('procedencia')->nullable();             // Ej: infestador, pre-cosecha, poda, etc.
            $table->decimal('cantidad_fresca', 15, 2)->nullable(); // Kg frescos reportados

            // === DATOS DEL FILTRADO ===
            $table->date('fecha_filtrado')->nullable();
            $table->decimal('cantidad_seca', 15, 2)->nullable();   // Kg filtrados/secos
            $table->string('condicion')->nullable();               // Venta, merma, etc.
            $table->string('item')->nullable();                    // Cochinilla seca/fresca/mamá
            $table->decimal('conversion_fresco_seco', 8, 4)->nullable(); // Kg seco / fresco (autocalculado si aplica)

            // === DATOS DE LA VENTA ===
            $table->date('fecha_venta')->nullable();
            $table->string('comprador')->nullable();              // Cliente final o comprador
            $table->string('cliente_facturacion')->nullable();    // Cliente según RUC (si distinto)
            $table->string('factura_numero')->nullable();         // Ej: F001-000123
            $table->string('tipo_venta')->nullable();             // blanco, negro, uso interno, etc.

            // === CALIDAD ===
            $table->float('punto_acido_carminico', 5, 2)->nullable(); // Valor objetivo
            $table->float('acido_carminico', 5, 2)->nullable();        // Medido en laboratorio

            // === DETALLES LOGÍSTICOS ===
            $table->integer('sacos')->nullable();
            $table->string('lote')->nullable();                   // Lote interno o contable
            $table->date('fecha_despacho')->nullable();           // Fecha real de despacho (envío)

            // === MONTO E INGRESOS ===
            $table->decimal('precio_venta_dolar', 10, 2)->nullable(); // Precio por kg en USD
            $table->decimal('ingresos_dolar', 15, 2)->nullable();     // Total en dólares
            $table->decimal('tipo_cambio', 6, 3)->nullable();         // Tipo de cambio oficial
            $table->decimal('ingresos_soles', 15, 2)->nullable();     // Total en soles (calculado)

            // === AGRUPADOR / RELACIÓN CON VENTA BASE ===
            $table->string('grupo_proceso')->nullable();          // Código que agrupa registros (manual)
            $table->unsignedBigInteger('venta_base_id')->nullable(); // Opcional: referencia a venta_cochinilla

            // === CONTABILIDAD / ESTADO ===
            $table->boolean('contabilizado')->default(false);
            $table->string('origen_datos')->nullable();           // manual, importado, generado, etc.

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_facturada_cochinillas');
    }
};
