<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * auditorias.modelo_id era varchar(10): no cabían las llaves de texto (p. ej. el código de mano de obra
 * "labores_mantenimiento") y auditar ese registro hacía fallar el guardado.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->string('modelo_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->string('modelo_id', 10)->nullable()->change();
        });
    }
};
