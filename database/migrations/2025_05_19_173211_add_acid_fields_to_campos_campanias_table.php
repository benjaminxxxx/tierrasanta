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
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->decimal('acid_prom', 5, 2)->nullable();
            $table->decimal('acid_infest', 5, 2)->nullable();
            $table->decimal('acid_secado', 5, 2)->nullable();
            $table->decimal('acid_poda_infest', 5, 2)->nullable();
            $table->decimal('acid_poda_losa', 5, 2)->nullable();
            $table->decimal('acid_tam', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'acid_prom',
                'acid_infest',
                'acid_secado',
                'acid_poda_infest',
                'acid_poda_losa',
                'acid_tam',
            ]);
        });
    }
};
