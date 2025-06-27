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
                ->onDelete('set null');

            $table->string('grupo_venta');
            $table->date('fecha_filtrado')->nullable(); // Fecha en la que se filtró el producto
            $table->decimal('cantidad_seca', 15, 2)->nullable(); // Cantidad filtrada (seco)
            $table->string('condicion')->default('venta'); // venta, merma, uso interno, etc.
            $table->string('cliente')->nullable(); 
            $table->string('item'); // Cochinilla seca, fresca, mama, venta mama
            $table->date('fecha_venta')->nullable(); // Fecha efectiva de venta
            $table->string('campo')->nullable(); 
            $table->text('observaciones')->nullable(); // Comentarios adicionales

            $table->boolean('aprobado_facturacion')->default(false);     // Fecha en que el admin aprobó los datos
            $table->timestamp('fecha_aprobacion_facturacion')->nullable();      // User que aprobó como admin
            $table->unsignedBigInteger('aprobador_facturacion')->nullable();    // User que registró facturación

            // Auditoría
            $table->timestamps();
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
