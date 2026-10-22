<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes manuales del PLAME por trabajador y concepto (vacaciones calculadas aparte, cuadre de vida ley/SCTR…).
 * - plame_ajustes: {"0803": {"monto": 9.76, "motivo": "..."}} — el monto que vale.
 * - plame_calculados: {"0803": 9.61, ...} — lo que calcula el sistema, para referencia.
 * Las columnas plame_* pasan a guardar el valor final (con ajustes). La vacación personalizada que ya existía
 * pasa a ser el ajuste del 0118.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_mensual_personals', function (Blueprint $table) {
            $table->json('plame_ajustes')->nullable()->after('vacaciones_negro');
            $table->json('plame_calculados')->nullable()->after('plame_ajustes');
        });

        DB::table('plan_mensual_personals')->whereNotNull('vacaciones_plame_personalizado')->orderBy('id')->get()
            ->each(fn($p) => DB::table('plan_mensual_personals')->where('id', $p->id)->update([
                'plame_ajustes' => json_encode(['0118' => ['monto' => round((float) $p->vacaciones_plame_personalizado, 2), 'motivo' => 'Vacaciones personalizadas']]),
                'plame_calculados' => json_encode(['0118' => round((float) $p->plame_0118_rem_vacacional, 2)]),
                'plame_0118_rem_vacacional' => $p->vacaciones_plame_personalizado,
            ]));
    }

    public function down(): void
    {
        DB::table('plan_mensual_personals')->whereNotNull('plame_calculados')->orderBy('id')->get()->each(function ($p) {
            $calc = json_decode($p->plame_calculados, true);
            if (isset($calc['0118'])) {
                DB::table('plan_mensual_personals')->where('id', $p->id)->update(['plame_0118_rem_vacacional' => $calc['0118']]);
            }
        });
        Schema::table('plan_mensual_personals', function (Blueprint $table) {
            $table->dropColumn(['plame_ajustes', 'plame_calculados']);
        });
    }
};
