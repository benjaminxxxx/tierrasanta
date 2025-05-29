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
        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria_pesticida')->nullable()->after('categoria');

            $table->foreign('categoria_pesticida')
                ->references('codigo')
                ->on('categoria_pesticidas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_pesticida']);
            $table->dropColumn('categoria_pesticida');
        });
    }
};
