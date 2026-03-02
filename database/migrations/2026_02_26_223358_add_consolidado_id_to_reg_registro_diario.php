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
        Schema::table('reg_registro_diario', function (Blueprint $table) {
            $table->unsignedBigInteger('consolidado_id')->nullable()->after('id');

            $table->foreign('consolidado_id')
                ->references('id')
                ->on('reg_resumen')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reg_registro_diario', function (Blueprint $table) {
            $table->dropForeign(['consolidado_id']);
            $table->dropColumn('consolidado_id');
        });
    }
};
