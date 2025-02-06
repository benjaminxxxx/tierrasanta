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
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->integer('indice')->nullable();
            $table->string('tipo_kardex',20)->nullable();
            $table->string('registro_carga', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->dropColumn(['indice','tipo_kardex','registro_carga']);
        });
    }
};
