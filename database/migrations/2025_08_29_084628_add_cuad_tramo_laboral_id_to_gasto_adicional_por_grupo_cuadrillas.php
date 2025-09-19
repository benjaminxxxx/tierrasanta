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
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->unsignedBigInteger('cuad_tramo_laboral_id')->nullable()->after('id');

            $table->foreign('cuad_tramo_laboral_id', 'fk_gasto_tra_lab1')
                ->references('id')
                ->on('cuad_tramo_laborals')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->dropForeign('fk_gasto_tra_lab1');
            $table->dropColumn('cuad_tramo_laboral_id');
        });
    }
};
