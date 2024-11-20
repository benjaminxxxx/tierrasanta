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
        Schema::table('compra_productos', function (Blueprint $table) {
            $table->string('tipo_compra_codigo', 4)->nullable();
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->string('tabla12_tipo_operacion')->nullable();
            $table->enum('tipo_kardex', ['blanco', 'negro']); // Blanco: Facturas, Negro: Boletas
            // Establecer las claves foráneas
            $table->foreign('tipo_compra_codigo')->references('codigo')->on('sunat_tabla10_tipo_comprobantes_pago')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_productos', function (Blueprint $table) {
            $table->dropForeign(['tipo_compra_codigo']);
            $table->dropColumn(['tipo_compra_codigo', 'serie','numero','tabla12_tipo_operacion','tipo_kardex']);
        });
    }
};
