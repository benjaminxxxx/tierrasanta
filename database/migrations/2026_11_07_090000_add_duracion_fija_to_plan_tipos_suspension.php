<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Suspensiones de duración fija (descanso médico, maternidad…): el trabajador no puede regresar antes del fin, así que
 * el registro diario no deja cambiar sus días ni ofrece "Regresó hoy". Las demás (vacaciones, licencias con o sin goce)
 * sí se pueden acortar. Se edita en el Excel de recursos (hoja Tipos de suspensión, columna "Duración fija").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_tipos_suspension', function (Blueprint $table) {
            $table->boolean('duracion_fija')->default(false)->after('incluye_domingos');
        });
        // Códigos SUNAT (tabla 21) que por ley tienen fechas fijas por certificado: 20 y 21 enfermedad o accidente, 22 maternidad
        DB::table('plan_tipos_suspension')->whereIn('codigo', ['20', '21', '22'])->update(['duracion_fija' => true]);
    }

    public function down(): void
    {
        Schema::table('plan_tipos_suspension', function (Blueprint $table) {
            $table->dropColumn('duracion_fija');
        });
    }
};
