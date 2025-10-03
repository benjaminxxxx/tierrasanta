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
        Schema::table('cuad_tramo_laborals', function (Blueprint $table) {
            $table->date('fecha_hasta_bono')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_tramo_laborals', function (Blueprint $table) {
            $table->dropColumn('fecha_hasta_bono');
        });
    }
};
