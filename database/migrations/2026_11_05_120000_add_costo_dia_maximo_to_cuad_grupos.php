<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tope del costo personalizado por día de un grupo (p. ej. cargadores, que cobran por camión y pueden ganar S/ 500).
 * Vacío = la regla general: hasta 3 veces el jornal del grupo ese día (frena montos mal digitados como 1101 por 110).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuad_grupos', function (Blueprint $table) {
            $table->decimal('costo_dia_maximo', 8, 2)->nullable()->after('costo_dia_sugerido');
        });
    }

    public function down(): void
    {
        Schema::table('cuad_grupos', function (Blueprint $table) {
            $table->dropColumn('costo_dia_maximo');
        });
    }
};
