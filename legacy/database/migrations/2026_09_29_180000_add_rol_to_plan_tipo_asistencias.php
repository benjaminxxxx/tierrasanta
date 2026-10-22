<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rol del tipo de asistencia: le da al código una función en el sistema.
 * - 'renuncia': marca el cese del trabajador (tareas pendientes de contrato sin finalizar,
 *   registros posteriores a la renuncia). No genera suspensión.
 * Un tipo con rol no se puede eliminar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_tipo_asistencias', function (Blueprint $table) {
            $table->string('rol', 30)->nullable()->after('sin_suspension');
        });

        DB::table('plan_tipo_asistencias')->where('codigo', 'R')
            ->update(['rol' => 'renuncia', 'sin_suspension' => true, 'plan_tipo_suspension_id' => null]);
    }

    public function down(): void
    {
        Schema::table('plan_tipo_asistencias', fn(Blueprint $t) => $t->dropColumn('rol'));
    }
};
