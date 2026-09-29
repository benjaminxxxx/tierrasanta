<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Elimina los triggers que mantenían ins_kardexes.stock_actual sumando compra_productos
     * (tabla antigua). Las compras nuevas van a compra_detalles y no los disparan, así que ese
     * número quedaba incompleto. El stock vigente vive en stocks_productos (StockService) y se
     * recalibra al generar los movimientos del kardex del año vigente.
     */
    public function up(): void
    {
        foreach (['trg_kardex_bi', 'trg_kardex_bu', 'trg_compra_ai', 'trg_compra_au', 'trg_compra_ad', 'trg_salida_ai', 'trg_salida_au', 'trg_salida_ad'] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }
        DB::unprepared('DROP PROCEDURE IF EXISTS sync_kardex_delta');
    }

    /**
     * Restaura los triggers tal como los creó la migración original.
     */
    public function down(): void
    {
        $original = require database_path('migrations/2026_03_17_231754_create_kardex_stock_triggers.php');
        $original->up();
    }
};
