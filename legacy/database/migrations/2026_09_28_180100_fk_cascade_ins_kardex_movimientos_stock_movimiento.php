<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Cada movimiento del kardex deriva de un movimiento de stock. Si el movimiento de stock se
     * elimina (se quitó una línea de compra o una salida), su reflejo en el kardex también: el
     * kardex queda desactualizado y se regenera de todos modos.
     */
    public function up(): void
    {
        // Referencias a movimientos de stock que ya no existen (restos de reversiones anteriores)
        DB::table('ins_kardex_movimientos')
            ->whereNotNull('stock_movimiento_id')
            ->whereNotExists(fn($q) => $q->from('movimientos_stock')->whereColumn('movimientos_stock.id', 'ins_kardex_movimientos.stock_movimiento_id'))
            ->update(['stock_movimiento_id' => null]);

        Schema::table('ins_kardex_movimientos', function (Blueprint $table) {
            $table->foreign('stock_movimiento_id', 'ins_kardex_mov_stock_mov_fk')
                ->references('id')->on('movimientos_stock')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ins_kardex_movimientos', function (Blueprint $table) {
            $table->dropForeign('ins_kardex_mov_stock_mov_fk');
        });
    }
};
