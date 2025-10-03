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
        Schema::table('cuad_resumen_por_tramos', function (Blueprint $table) {
            DB::statement("ALTER TABLE cuad_resumen_por_tramos MODIFY COLUMN tipo ENUM('adicional','sueldo','bono') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_resumen_por_tramos', function (Blueprint $table) {
            DB::statement("ALTER TABLE cuad_resumen_por_tramos MODIFY COLUMN tipo ENUM('adicional','sueldo') NOT NULL");
        });
    }
};
