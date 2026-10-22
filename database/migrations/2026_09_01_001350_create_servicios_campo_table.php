<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_campo', function (Blueprint $table) {
            $table->id();
            $table->string('servicio');
            $table->string('unidad', 50);
            $table->decimal('costo_unitario', 14, 6);
            $table->string('tipo_comprobante', 30);
            $table->string('numero_comprobante', 50)->nullable();
            $table->string('tipo_costo', 10);
            $table->date('fecha_comprobante');
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);
            $table->decimal('cantidad_total', 12, 3)->default(0.000);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('igv', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->decimal('costo_total', 12, 2)->default(0.00);
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
