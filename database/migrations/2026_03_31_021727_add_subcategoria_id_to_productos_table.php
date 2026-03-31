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
            $table->unsignedBigInteger('subcategoria_id')
                ->nullable()
                ->after('categoria_codigo');

            $table->foreign('subcategoria_id')
                ->references('id')
                ->on('ins_subcategorias')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['subcategoria_id']);
            $table->dropColumn('subcategoria_id');
        });
    }
};
