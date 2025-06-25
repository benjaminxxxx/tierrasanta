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

            // =================== PROCESO 1 - REGISTRO RÁPIDO ===================

            $table->unsignedBigInteger('cochinilla_ingreso_id')->nullable();
            $table->foreign('cochinilla_ingreso_id')
                ->references('id')->on('cochinilla_ingresos')
                ->onDelete('restrict');

            $table->string('grupo_venta'); // Agrupador lógico para relación venta múltiple
            $table->string('origen_especial')->nullable();
            $table->date('fecha_filtrado')->nullable(); // Fecha en la que se filtró el producto
            $table->decimal('cantidad_seca', 15, 2)->nullable(); // Cantidad filtrada (seco)
            $table->string('condicion')->default('venta'); // venta, merma, uso interno, etc.
            $table->string('cliente')->nullable(); // Cliente o destino
            $table->string('cliente_facturacion')->nullable();
            $table->string('item'); // Cochinilla seca, fresca, mama, venta mama
            $table->date('fecha_venta')->nullable(); // Fecha efectiva de venta
            $table->string('campo')->nullable(); // Campo de cosecha (puede ser null para ventas negras, internas)
            $table->string('procedencia')->nullable(); // infestador, poda mama, etc.
            $table->enum('tipo_venta', ['blanco', 'negro'])->nullable(); // blanco: contabilidad oficial, negro: no
            $table->text('observaciones')->nullable(); // Comentarios adicionales
            $table->boolean('contabilizado')->default(false); // Ya fue revisado por contabilidad

            // =================== PROCESOS FUTUROS - FACTURACIÓN, CALIDAD, INGRESOS ===================
            $table->string('factura_numero')->nullable(); // Ej: F001-01234 (puede agrupar varias ventas)
            $table->decimal('precio_venta_dolar', 10, 2)->nullable(); // Precio por kg en USD
            $table->decimal('ingresos_dolar', 15, 2)->nullable(); // Monto total en USD
            $table->decimal('tipo_cambio', 6, 3)->nullable(); // Ej: 3.768
            $table->decimal('ingresos_soles', 15, 2)->nullable(); // Monto total en PEN

            // Información de calidad
            $table->float('punto_acido_carminico', 10, 2)->nullable(); // % estimado
            $table->float('acido_carminico', 10, 2)->nullable(); // valor real en laboratorio (si aplica)
            $table->integer('sacos')->nullable(); // Número de sacos entregados

            // Infestador adicional
            $table->string('infestador_campo')->nullable(); // Si procede de infestador (campo)
            $table->string('tipo_infestador')->nullable(); // Tipo de infestador (poda, mama, etc.)

            $table->boolean('aprobado_admin')->default(false); // Confirmación de datos por entregador (opcional)
            $table->boolean('aprobado_facturacion')->default(false);     // Fecha en que el admin aprobó los datos
            $table->timestamp('fecha_aprobacion_admin')->nullable();
            $table->timestamp('fecha_aprobacion_facturacion')->nullable();
            $table->unsignedBigInteger('aprobador_admin')->nullable();         // User que aprobó como admin
            $table->unsignedBigInteger('aprobador_facturacion')->nullable();    // User que registró facturación

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
