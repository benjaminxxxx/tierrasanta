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
        Schema::table('cuadrilla_horas', function (Blueprint $table) {
            $table->decimal('horas_contabilizadas', 4, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuadrilla_horas', function (Blueprint $table) {
            $table->dropColumn(['horas_contabilizadas']);
        });
    }
};
