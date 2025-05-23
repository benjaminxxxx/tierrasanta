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
        Schema::table('kardex', function (Blueprint $table) {
            $table->year('anio');
            $table->boolean('es_combustible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kardex', function (Blueprint $table) {
            $table->dropUnique('kardex_anio_combustible_unique');
            $table->dropColumn(['anio', 'es_combustible']);
        });
    }
};
