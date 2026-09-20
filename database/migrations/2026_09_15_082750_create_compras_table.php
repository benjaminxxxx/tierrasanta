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
       Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
            $table->foreignId('almacen_id')->constrained('almacenes');

            $table->enum('moneda', ['PEN', 'USD'])->default('PEN');
            $table->decimal('tipo_cambio', 8, 4)->default(1.0000);

            // Reemplaza a tipo_compra_codigo usando catálogo SUNAT Tabla 10 o enum
            $table->string('tipo_comprobante_codigo', 4)->nullable();
            $table->foreign('tipo_comprobante_codigo')->references('codigo')->on('sunat_tabla10_tipo_comprobantes_pago')->nullOnDelete();

            $table->string('serie', 20)->nullable();
            $table->string('numero', 30)->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();

            $table->enum('forma_pago', ['contado', 'credito'])->default('contado');
            $table->enum('tipo_kardex', ['blanco', 'negro'])->default('blanco');

            $table->decimal('subtotal_neto', 12, 4)->default(0);
            $table->decimal('igv_total', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->default(0);

            $table->text('notas')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
