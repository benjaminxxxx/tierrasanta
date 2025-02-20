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
        Schema::table('resumen_consumo_productos', function (Blueprint $table) {
            $table->string('orden_compra', 12)->nullable();
            $table->text('tienda_comercial')->nullable();
            $table->string('factura', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumen_consumo_productos', function (Blueprint $table) {
            $table->dropColumn([
                'orden_compra',
                'tienda_comercial',
                'factura'
            ]);
        });
    }
};
