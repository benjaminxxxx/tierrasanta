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
        Schema::create('venta_cochinillas', function (Blueprint $table) {
            $table->id();

            // Información básica inicial
            $table->date('fecha_ingreso')->nullable(); // Ej: 5/04/2025
            $table->date('fecha_filtrado')->nullable(); // Ej: 5/04/2025
            $table->date('area')->nullable(); // Ej: 5/04/2025
            $table->date('fecha_venta')->nullable(); // Ej: 5/04/2025

            $table->string('nombre_comprador')->nullable(); // Ej: Diana
            $table->string('tipo_venta')->nullable(); // "Factura" o "NG"
            $table->string('factura_numero')->nullable(); // E001-165

            // Información del lote / campo
            $table->string('lote')->nullable(); // Ej: B-10
            $table->decimal('kg', 10)->nullable(); // Peso seco vendido
            $table->string('campo')->nullable(); // Campo de origen
            $table->string('procedencia')->nullable(); // infestador, poda mama, etc.

            // Composición / calidad
            $table->float('precio_venta_dolar', 10, 2)->nullable();
            $table->float('punto_acido_carminico', 10, 2)->nullable();
            $table->float('acido_carminico', 10, 2)->nullable();
            $table->integer('sacos')->nullable();

            // Cálculo financiero
            $table->float('ingresos_dolar', 15, 2)->nullable();
            $table->float('tipo_cambio', 6, 3)->nullable();
            $table->float('ingresos_soles', 15, 2)->nullable();

            $table->string('estado')->nullable(); // blanco / negro
            $table->string('infestador_campo')->nullable();
            $table->string('tipo_infestador')->nullable();
            $table->text('observaciones')->nullable();

            $table->float('cantidad_seca',15, 2)->nullable(); // blanco / negro
            $table->string('condicion')->nullable();

            // Auditoría
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_cochinillas');
    }
};
