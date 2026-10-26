<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos bloqueados: ya no existen en la empresa nueva, pero tienen historia (campañas, costos). No se borran;
 * se ocultan de los selectores y de las listas de trabajo (Campo::scopeActivos).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('campos', function (Blueprint $table) {
            $table->boolean('bloqueado')->default(false)->after('alias')
                ->comment('true = campo que ya no se usa (se conserva su historia, no aparece en selectores)');
        });
    }

    public function down(): void
    {
        Schema::table('campos', function (Blueprint $table) {
            $table->dropColumn('bloqueado');
        });
    }
};
