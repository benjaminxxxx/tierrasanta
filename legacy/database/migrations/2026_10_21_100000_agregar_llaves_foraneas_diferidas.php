<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Llaves foráneas que algunas migraciones antiguas declaraban hacia tablas que se crean después (en una instalación
 * limpia fallaban). Esas migraciones ahora crean solo la columna y la llave se agrega aquí. En una base que ya las tiene
 * (dominio antiguo) no hace nada.
 */
return new class extends Migration
{
    /** [tabla, columna, tabla referida, al borrar] */
    private const LLAVES = [
        ['cuad_gastos_grupos', 'desglose_detalle_id', 'desglose_detalles', 'set null'],
        ['cuad_registros_diarios', 'desglose_detalle_id', 'desglose_detalles', 'set null'],
        ['cuad_bonos_actividades', 'desglose_detalle_id', 'desglose_detalles', 'set null'],
        ['almacen_producto_salidas', 'uso_id', 'ins_usos', 'set null'],
    ];

    public function up(): void
    {
        foreach (self::LLAVES as [$tabla, $columna, $referida, $alBorrar]) {
            if (!Schema::hasTable($tabla) || !Schema::hasTable($referida) || $this->tieneLlave($tabla, $columna)) {
                continue;
            }
            Schema::table($tabla, function (Blueprint $t) use ($columna, $referida, $alBorrar) {
                $t->foreign($columna)->references('id')->on($referida)->onDelete($alBorrar);
            });
        }
    }

    public function down(): void
    {
        // Sin reversa: en el dominio antiguo estas llaves ya existían antes de esta migración
    }

    private function tieneLlave(string $tabla, string $columna): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tabla)
            ->where('COLUMN_NAME', $columna)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }
};
