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
        Schema::table('labores', function (Blueprint $table) {
            $table->string('codigo_mano_obra')->nullable();

            $table->foreign('codigo_mano_obra')
                ->references('codigo')
                ->on('mano_obras')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->dropForeign(['codigo_mano_obra']);
            $table->dropColumn('codigo_mano_obra');
        });
    }
};
