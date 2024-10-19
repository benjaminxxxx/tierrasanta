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
        Schema::table('planillas_blanco', function (Blueprint $table) {
            $table->decimal('factor_remuneracion_basica', 15, 12)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planillas_blanco', function (Blueprint $table) {
            // Eliminar el campo 'factor_remuneracion_basica' en caso de rollback
            $table->dropColumn('factor_remuneracion_basica');
        });
    }
};
