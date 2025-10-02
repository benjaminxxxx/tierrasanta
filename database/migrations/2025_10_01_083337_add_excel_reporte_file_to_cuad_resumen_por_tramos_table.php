<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuad_resumen_por_tramos', function (Blueprint $table) {
            $table->string('excel_reporte_file')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cuad_resumen_por_tramos', function (Blueprint $table) {
            $table->dropColumn('excel_reporte_file');
        });
    }
};
