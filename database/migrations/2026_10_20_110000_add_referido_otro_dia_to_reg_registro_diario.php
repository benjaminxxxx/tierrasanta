<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riego: registro "de otro día". Un regador que trabaja un domingo se anota el lunes (así se paga en el reporte diario
 * del lunes). Esas filas no se cruzan con otros regadores (no son ayuda: el riego sí ocurrió, otro día) y su jornal se
 * calcula aparte del trabajo del propio día (no se fusionan con sus horas del lunes).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reg_registro_diario', function (Blueprint $table) {
            $table->boolean('referido_otro_dia')->default(false)->after('por_acumulacion');
        });
    }

    public function down(): void
    {
        Schema::table('reg_registro_diario', function (Blueprint $table) {
            $table->dropColumn('referido_otro_dia');
        });
    }
};
