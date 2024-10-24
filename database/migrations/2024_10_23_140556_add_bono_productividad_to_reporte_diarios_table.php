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
        Schema::table('reporte_diarios', function (Blueprint $table) {
            $table->decimal('bono_productividad', 10, 2)->nullable(); // Cambia el tipo y la precisión según tus necesidades
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporte_diarios', function (Blueprint $table) {
            $table->dropColumn('bono_productividad');
        });
    }
};
