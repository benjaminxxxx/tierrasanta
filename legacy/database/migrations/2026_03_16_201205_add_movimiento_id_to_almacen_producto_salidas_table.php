<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->foreignId('movimiento_id')
                ->nullable()
                ->after('tipo_kardex')
                ->constrained('ins_kardex_movimientos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->dropForeign(['movimiento_id']);
            $table->dropColumn('movimiento_id');
        });
    }
};
