<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El feriado (FR) se paga como día laborado: en el PLAME no es suspensión de labores.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('plan_tipo_asistencias')->where('codigo', 'FR')
            ->update(['sin_suspension' => true, 'plan_tipo_suspension_id' => null]);
    }

    public function down(): void
    {
        DB::table('plan_tipo_asistencias')->where('codigo', 'FR')->update(['sin_suspension' => false]);
    }
};
