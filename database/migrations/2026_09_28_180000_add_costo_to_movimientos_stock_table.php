<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * El movimiento de entrada (compra) guarda su costo: es parte de la historia real y es lo que
     * el kardex lee para valorizar. Cambiar el precio de una compra actualiza este costo (y deja el
     * kardex desactualizado) sin tocar cantidades ni stock.
     */
    public function up(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->decimal('costo_unitario', 18, 6)->nullable()->after('cantidad');
            $table->decimal('costo_total', 18, 4)->nullable()->after('costo_unitario');
        });

        // Entradas existentes: costo desde su línea de compra
        DB::table('movimientos_stock as m')
            ->join('compra_detalles as d', 'd.id', '=', 'm.origen_id')
            ->where('m.origen_type', 'App\\Models\\CompraDetalle')
            ->update([
                'm.costo_total' => DB::raw('d.costo_total_kardex'),
                'm.costo_unitario' => DB::raw('d.costo_unitario_base'),
            ]);
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario', 'costo_total']);
        });
    }
};
