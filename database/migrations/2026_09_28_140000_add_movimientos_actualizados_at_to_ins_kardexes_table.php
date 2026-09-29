<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fecha de corte: momento en que se generaron por última vez los movimientos del kardex.
     * Los costos de salida (almacen_producto_salidas.total_costo) son confiables hasta ese corte;
     * si después se crean, editan o eliminan compras/salidas, el kardex queda desactualizado.
     */
    public function up(): void
    {
        Schema::table('ins_kardexes', function (Blueprint $table) {
            $table->timestamp('movimientos_actualizados_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ins_kardexes', function (Blueprint $table) {
            $table->dropColumn('movimientos_actualizados_at');
        });
    }
};
