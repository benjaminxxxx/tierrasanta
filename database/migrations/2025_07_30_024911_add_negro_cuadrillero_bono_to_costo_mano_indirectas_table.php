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
        Schema::table('costo_mano_indirectas', function (Blueprint $table) {
            $table->decimal('negro_cuadrillero_bono', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costo_mano_indirectas', function (Blueprint $table) {
            $table->dropColumn('negro_cuadrillero_bono');
        });
    }
};
