<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->foreign('cuadrillero_id', 'fk_cuad_reg_diarios_cuadrillero')
                ->references('id')
                ->on('cuadrilleros')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropForeign('fk_cuad_reg_diarios_cuadrillero');
        });
    }
};
