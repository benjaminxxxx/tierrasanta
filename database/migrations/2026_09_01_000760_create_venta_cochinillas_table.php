<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_cochinillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')->nullable()->constrained('cochinilla_ingresos')->nullOnDelete();
            $table->string('grupo_venta');
            $table->date('fecha_filtrado')->nullable();
            $table->decimal('cantidad_seca', 15, 2)->nullable();
            $table->string('condicion')->default('venta');
            $table->string('cliente')->nullable();
            $table->string('item');
            $table->date('fecha_venta')->nullable();
            $table->string('campo')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('aprobado_facturacion')->default(false);
            $table->timestamp('fecha_aprobacion_facturacion')->nullable();
            $table->unsignedBigInteger('aprobador_facturacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_cochinillas');
    }
};
