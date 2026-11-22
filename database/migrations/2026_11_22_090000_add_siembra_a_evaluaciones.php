<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluaciones por siembra (Lucio, 22/11/2026): con doble siembra cada una se evalúa aparte.
 *  - Población: una evaluación por siembra (antes una por campaña).
 *  - Brotes: una evaluación por siembra y fecha (antes por campaña y fecha).
 * La columna queda vacía hasta correr `php artisan evaluaciones:vincular-siembras`, que pasa las existentes a la
 * primera siembra de su campaña. El candado para no quitar una siembra con evaluaciones está en CampaniaSectorProceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eval_poblacion_plantas', 'campania_sector_id')) {
            Schema::table('eval_poblacion_plantas', function (Blueprint $table) {
                $table->foreignId('campania_sector_id')->nullable()->after('campania_id')->constrained('campania_sectores')->nullOnDelete();
                $table->index('campania_id', 'eval_poblacion_campania_idx');
            });
            $this->quitarUnico('eval_poblacion_plantas', ['campania_id']);
            Schema::table('eval_poblacion_plantas', function (Blueprint $table) {
                $table->unique('campania_sector_id', 'eval_poblacion_sector_unique');
            });
        }

        if (!Schema::hasColumn('eval_brotes_por_pisos', 'campania_sector_id')) {
            Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
                $table->foreignId('campania_sector_id')->nullable()->after('campania_id')->constrained('campania_sectores')->nullOnDelete();
                $table->index('campania_id', 'eval_brotes_campania_idx');
            });
            $this->quitarUnico('eval_brotes_por_pisos', ['campania_id', 'fecha']);
            Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
                $table->unique(['campania_sector_id', 'fecha'], 'eval_brotes_sector_fecha_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eval_brotes_por_pisos', 'campania_sector_id')) {
            // Primero la llave foránea: MySQL usa el índice único de la siembra para sostenerla
            Schema::table('eval_brotes_por_pisos', fn(Blueprint $table) => $table->dropForeign(['campania_sector_id']));
            Schema::table('eval_brotes_por_pisos', function (Blueprint $table) {
                $table->unique(['campania_id', 'fecha'], 'eval_brotes_campania_fecha_unique');
                $table->dropUnique('eval_brotes_sector_fecha_unique');
                $table->dropIndex('eval_brotes_campania_idx');
                $table->dropColumn('campania_sector_id');
            });
        }
        if (Schema::hasColumn('eval_poblacion_plantas', 'campania_sector_id')) {
            Schema::table('eval_poblacion_plantas', fn(Blueprint $table) => $table->dropForeign(['campania_sector_id']));
            Schema::table('eval_poblacion_plantas', function (Blueprint $table) {
                $table->unique('campania_id');
                $table->dropUnique('eval_poblacion_sector_unique');
                $table->dropIndex('eval_poblacion_campania_idx');
                $table->dropColumn('campania_sector_id');
            });
        }
    }

    /** El nombre del índice puede variar entre bases antiguas y nuevas: se busca por sus columnas. */
    private function quitarUnico(string $tabla, array $columnas): void
    {
        foreach (Schema::getIndexes($tabla) as $indice) {
            if ($indice['unique'] && !$indice['primary'] && $indice['columns'] === $columnas) {
                Schema::table($tabla, fn(Blueprint $table) => $table->dropUnique($indice['name']));
            }
        }
    }
};
