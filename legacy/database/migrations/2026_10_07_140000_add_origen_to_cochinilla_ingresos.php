<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaña dueña de la cochinilla de cada ingreso (a la que se le carga la venta).
 *
 * campo_campania_id sigue siendo la campaña del campo donde se recogió (ahí van el costo del trabajo y la
 * detección de cosecha). Casi siempre son la misma; no lo son en la cochinilla recogida de los infestadores:
 * si se cosechó A1 para infestar el campo 5, las cajitas se recogen en el 5, pero la cochinilla es de la campaña
 * de A1 que se cosechó. Ver CochinillaOrigenProceso.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->string('campo_origen', 100)->nullable()->after('campo_campania_id')->comment('Campo de donde es la cochinilla');
            $table->foreignId('campania_origen_id')->nullable()->after('campo_origen')->constrained('campos_campanias')->nullOnDelete()
                ->comment('Campaña dueña de la cochinilla: a la que se carga la venta');
            $table->string('origen_estado', 20)->nullable()->after('campania_origen_id')
                ->comment('propio | infestacion | varios | sin_infestacion | manual');
            $table->string('origen_detalle', 300)->nullable()->after('origen_estado');
        });

        app(\App\Services\Cochinilla\Origen\CochinillaOrigenProceso::class)->recalcularTodos();
    }

    public function down(): void
    {
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campania_origen_id');
            $table->dropColumn(['campo_origen', 'origen_estado', 'origen_detalle']);
        });
    }
};
