<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las campañas consultan actividades por campo y fecha (estado de cosecha, cobertura de días sin campaña).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->index(['campo', 'fecha'], 'actividades_campo_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropIndex('actividades_campo_fecha_index');
        });
    }
};
