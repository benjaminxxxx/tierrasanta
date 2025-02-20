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
        Schema::table('registro_productividads', function (Blueprint $table) {
            $table->decimal('kg_8', 8, 2)->nullable(); // Kilos estándar para la jornada
            $table->decimal('valor_kg_adicional', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_productividads', function (Blueprint $table) {
            $table->dropColumn([
                'kg_8',
                'valor_kg_adicional'
            ]);
        });
    }
};
