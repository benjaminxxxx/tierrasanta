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
            $table->decimal('total', 10, 2)->default(0); // Reemplaza 'existing_column' por el nombre de una columna existente si quieres un orden específico
            $table->decimal('stock', 10, 3)->default(0);
            $table->date('fecha_termino')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_productos', function (Blueprint $table) {
            $table->dropColumn(['total', 'stock', 'fecha_termino']);
        });
    }
};
