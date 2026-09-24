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
        Schema::create('desglose_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desglose_id')->constrained('desgloses')->onDelete('cascade');
            $table->string('nro_documento')->nullable(); // Ej: 2419, 2420
            $table->string('referencia_nro_caja')->nullable();
            $table->string('razon_social')->nullable();
            
            // Tipo de gasto para agrupar según la lógica de negocio
            $table->enum('tipo_gasto', ['PAGO_CUADRILLA', 'PAGO_BONOS_ACUMULADOS', 'GASTO_ADICIONAL']);
            
            $table->string('descripcion'); // Ej: "Cuad. Semanal Santa Rita del 07 al 12 septiembre"
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_resultante', 12, 2); // Saldo progresivo columna "Saldo"
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desglose_detalles');
    }
};