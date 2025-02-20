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
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->text('gasto_planilla_file')->nullable();
            $table->text('gasto_cuadrilla_file')->nullable();
            $table->text('gasto_resumen_bdd_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'gasto_planilla_file',
                'gasto_cuadrilla_file',
                'gasto_resumen_bdd_file',
            ]);
        });
    }
};
