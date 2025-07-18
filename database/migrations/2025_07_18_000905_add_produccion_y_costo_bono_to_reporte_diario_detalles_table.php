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
        Schema::table('reporte_diario_detalles', function (Blueprint $table) {
            $table->decimal('produccion', 10, 2)->nullable()->after('labor');
            $table->decimal('costo_bono', 10, 2)->nullable()->after('produccion');
        });
    }

    public function down(): void
    {
        Schema::table('reporte_diario_detalles', function (Blueprint $table) {
            $table->dropColumn(['produccion', 'costo_bono']);
        });
    }
};
