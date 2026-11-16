<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Cada grupo del mapa de etapas es una SIEMBRA (Lucio, 16/11/2026): un grupo solo existe porque esas hileras se
 * sembraron en otra fecha (ej. sembrado a 4 m y meses después el relleno a 2 m). Se guarda su fecha de siembra y se
 * vuelven a detectar los mapas armados por el sistema (los corregidos a mano se respetan): antes los grupos salían de
 * tandas de infestación o poda, que no son motivo de otro grupo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('campania_sectores', 'fecha_siembra')) {
            Schema::table('campania_sectores', function (Blueprint $table) {
                $table->date('fecha_siembra')->nullable()->after('nombre');
            });
        }
        Artisan::call('campania:detectar-sectores');
    }

    public function down(): void
    {
        if (Schema::hasColumn('campania_sectores', 'fecha_siembra')) {
            Schema::table('campania_sectores', fn(Blueprint $table) => $table->dropColumn('fecha_siembra'));
        }
    }
};
