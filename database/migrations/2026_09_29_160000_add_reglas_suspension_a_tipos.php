<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas para sugerir suspensiones desde el registro diario:
 * - plan_tipos_suspension.incluye_domingos: los domingos sin registro entre dos días de la
 *   misma suspensión forman parte de ella (vacaciones, enfermedad, maternidad: días calendario).
 * - plan_tipo_asistencias.sin_suspension: el código no genera suspensión (ej. feriado).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_tipos_suspension', function (Blueprint $table) {
            $table->boolean('incluye_domingos')->default(false)->after('descripcion_corta');
        });
        DB::table('plan_tipos_suspension')->whereIn('codigo', ['20', '21', '22', '23'])->update(['incluye_domingos' => true]);

        Schema::table('plan_tipo_asistencias', function (Blueprint $table) {
            $table->boolean('sin_suspension')->default(false)->after('plan_tipo_suspension_id');
        });
        DB::table('plan_tipo_asistencias')->where('codigo', 'A')->update(['sin_suspension' => true]);
    }

    public function down(): void
    {
        Schema::table('plan_tipo_asistencias', fn(Blueprint $t) => $t->dropColumn('sin_suspension'));
        Schema::table('plan_tipos_suspension', fn(Blueprint $t) => $t->dropColumn('incluye_domingos'));
    }
};
