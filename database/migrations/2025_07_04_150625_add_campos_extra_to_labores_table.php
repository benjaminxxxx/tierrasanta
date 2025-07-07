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
        Schema::table('labores', function (Blueprint $table) {
            $table->integer('codigo')->unique()->nullable();
            $table->integer('estandar_produccion')->nullable();
            $table->string('unidades', 20)->nullable(); // ejemplo: "kg", "bandeja", etc.
            $table->text('tramos_bonificacion')->nullable(); // almacenará JSON si se desea
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->dropColumn([
                'codigo',
                'estandar_produccion',
                'unidades',
                'tramos_bonificacion'
            ]);
        });
    }
};
