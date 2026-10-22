<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desglose_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desglose_id')->constrained('desgloses')->cascadeOnDelete();
            $table->string('nro_documento')->nullable();
            $table->string('referencia_nro_caja')->nullable();
            $table->string('razon_social')->nullable();
            $table->enum('tipo_gasto', ['PAGO_CUADRILLA', 'PAGO_BONOS_ACUMULADOS', 'GASTO_ADICIONAL']);
            $table->string('descripcion');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_resultante', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desglose_detalles');
    }
};
