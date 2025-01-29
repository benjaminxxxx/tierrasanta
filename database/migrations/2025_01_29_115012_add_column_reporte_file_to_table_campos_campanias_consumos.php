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
        Schema::table('campos_campanias_consumos', function (Blueprint $table) {
            $table->text('reporte_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias_consumos', function (Blueprint $table) {
            $table->dropColumn([
                'reporte_file',
            ]);
        });
    }
};
