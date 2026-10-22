<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * No todo ingreso de cochinilla se vende. La mamá que se cosecha para infestar vuelve después como un ingreso nuevo
 * de infestadores (con otro peso): venderla también duplicaría la cochinilla. Un lote puede ir parte a venta y parte
 * a infestación con sublotes de distinto tipo (p. ej. "Mama – Venta" y "Poda – Mama"); sus kg vendibles son los de
 * los sublotes vendibles.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cochinilla_observaciones', function (Blueprint $table) {
            $table->boolean('es_vendible')->default(true)->after('es_cosecha_mama')
                ->comment('false = va a infestación y vuelve como ingreso de infestadores: no se vende');
        });
        DB::table('cochinilla_observaciones')->whereIn('codigo', ['mama', 'poda_mama'])->update(['es_vendible' => false]);
    }

    public function down(): void
    {
        Schema::table('cochinilla_observaciones', fn(Blueprint $table) => $table->dropColumn('es_vendible'));
    }
};
