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
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->decimal('stock_disponible', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->dropColumn('stock_disponible');
        });
    }
};
