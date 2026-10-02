<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Labores de suspensión: una labor puede representar un tipo de asistencia (DM, V, FR…).
 *
 * Así un día con asistencia A puede llevar en su detalle "4 h de labor + 4 h de descanso médico (97)"
 * y el sistema sabe que esas horas son de suspensión (sugerencias PLAME, BDD en FDM con ese código).
 * Al revés, un día con asistencia DM entra a la BDD con la labor vinculada a DM.
 */
return new class extends Migration {
    /** código de labor => código de asistencia (labores que ya existían para esto) */
    private const VINCULOS = [
        96 => 'V',   // Vacaciones
        97 => 'DM',  // Descanso médico
        98 => 'AM',  // Atención médica
        99 => 'LCG', // Licencias
        199 => 'FR', // Feriado
    ];

    public function up(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->string('tipo_asistencia_codigo', 10)->nullable()->after('codigo_mano_obra')
                ->comment('Si la labor representa una suspensión/no laborado: código de plan_tipo_asistencias (DM, V, FR…)');
            $table->index('tipo_asistencia_codigo');
        });

        $codigosAsistencia = DB::table('plan_tipo_asistencias')->pluck('codigo')->flip();
        foreach (self::VINCULOS as $codigoLabor => $codigoAsistencia) {
            if (!isset($codigosAsistencia[$codigoAsistencia])) {
                continue;
            }
            DB::table('labores')->where('codigo', $codigoLabor)->update(['tipo_asistencia_codigo' => $codigoAsistencia]);
            // Todo lo no laborado va a FDM: su mano de obra también
            DB::table('labores')->where('codigo', $codigoLabor)
                ->where(fn($q) => $q->whereNull('codigo_mano_obra')->orWhere('codigo_mano_obra', ''))
                ->update(['codigo_mano_obra' => 'fdm']);
        }
    }

    public function down(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            $table->dropIndex(['tipo_asistencia_codigo']);
            $table->dropColumn('tipo_asistencia_codigo');
        });
    }
};
