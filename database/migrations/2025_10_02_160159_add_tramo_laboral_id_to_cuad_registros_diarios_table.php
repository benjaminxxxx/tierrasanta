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
            // Tramo laboral actual
            $table->unsignedBigInteger('tramo_laboral_id')->nullable();
            // Relación principal (cascade)
            $table->foreign('tramo_laboral_id')
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
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropForeign(['tramo_laboral_id']);
            $table->dropColumn('tramo_laboral_id');
        });
    }
};
