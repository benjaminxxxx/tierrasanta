<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Datos de la hoja oficial de infestación (Propuesta 2) que no se guardaban: % de malla raschel y procedencia de los infestadores. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cochinilla_infestaciones', function (Blueprint $table) {
            $table->decimal('porcentaje_malla_rashel', 6, 2)->nullable()->after('capacidad_envase');
            $table->string('procedencia_infestadores', 80)->nullable()->after('porcentaje_malla_rashel');
        });
    }

    public function down(): void
    {
        Schema::table('cochinilla_infestaciones', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_malla_rashel', 'procedencia_infestadores']);
        });
    }
};
