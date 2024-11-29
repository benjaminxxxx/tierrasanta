<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            
            $table->foreignId('maquinaria_id')->nullable()
            ->constrained('maquinarias')
            ->onDelete('set null');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacen_producto_salidas', function (Blueprint $table) {
            $table->dropForeign(['maquinaria_id']);
            $table->dropColumn('maquinaria_id');
        });
    }
};
