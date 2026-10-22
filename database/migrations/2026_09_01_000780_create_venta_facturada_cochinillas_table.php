<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_facturada_cochinillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->cascadeOnDelete();
            $table->date('fecha')->nullable();
            $table->string('factura')->nullable();
            $table->string('tipo_venta')->nullable();
            $table->string('comprador')->nullable();
            $table->string('lote')->nullable();
            $table->decimal('kg', 15, 2)->nullable();
            $table->string('procedencia')->nullable();
            $table->decimal('precio_venta_dolares', 15, 2)->nullable();
            $table->decimal('punto_acido_carminico', 5, 2)->nullable();
            $table->decimal('factor_saco', 5, 2)->default(30.00);
            $table->decimal('tipo_cambio', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_facturada_cochinillas');
    }
};
