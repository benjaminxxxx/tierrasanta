<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Los rellenos ya no se detectan solos (un relleno y un replante se registran igual): la tarjeta los propone como
 * "posibles rellenos" y una persona confirma ("Es otra siembra") o descarta ("Es replante"). Lo descartado se guarda en
 * campos_campanias.rellenos_descartados (fechas de inicio de las tandas) para no volver a proponerlo.
 * Se vuelven a detectar los mapas armados por el sistema (quedan con una siembra) y se limpian del registro los
 * rellenos que el sistema había creado solo; lo confirmado o corregido a mano se respeta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('campos_campanias', 'rellenos_descartados')) {
            Schema::table('campos_campanias', function (Blueprint $table) {
                $table->json('rellenos_descartados')->nullable();
            });
        }
        Artisan::call('campania:detectar-sectores');
        Artisan::call('campania:sincronizar-siembras');
    }

    public function down(): void
    {
        if (Schema::hasColumn('campos_campanias', 'rellenos_descartados')) {
            Schema::table('campos_campanias', fn(Blueprint $table) => $table->dropColumn('rellenos_descartados'));
        }
    }
};
