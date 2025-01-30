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
            $table->enum('tipo_kardex', ['blanco', 'negro','-']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumen_consumo_productos', function (Blueprint $table) {
            $table->dropColumn(['tipo_kardex']);
        });
    }
};
