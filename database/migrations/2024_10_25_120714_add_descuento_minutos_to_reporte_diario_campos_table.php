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
        Schema::table('reporte_diario_campos', function (Blueprint $table) {
            $table->integer('descuento_minutos')->nullable();
            // Cambia 'existing_column' por el nombre del campo después del cual deseas agregar 'descuento_minutos'.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporte_diario_campos', function (Blueprint $table) {
            $table->dropColumn('descuento_minutos');
        });
    }
};
