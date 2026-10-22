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
            $table->date('fecha')->nullable();             // Fecha de ingreso de la cochinilla
            $table->string('factura')->nullable();                // F001-123
            $table->string('tipo_venta')->nullable();                   // factura o ng
            $table->string('comprador')->nullable();             // Cliente
            $table->string('lote')->nullable();             
            $table->decimal('kg', 15, 2)->nullable(); 
            $table->string('procedencia')->nullable();
            $table->decimal('precio_venta_dolares', 15, 2)->nullable();  
            $table->decimal('punto_acido_carminico', 5, 2)->nullable();   
            $table->decimal('factor_saco', 5, 2)->default(30);    
            $table->decimal('tipo_cambio')->nullable();   
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
