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
        Schema::create('registro_productividad_cantidads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cuadrillero_id')->nullable()->constrained('cuadrilleros')->onDelete('cascade');
            $table->decimal('kg', 5, 2);
            $table->decimal('kg_subtotal', 5, 2);
            $table->unsignedBigInteger('registro_productividad_detalles_id')->nullable();

            $table->foreign('registro_productividad_detalles_id','fk_reg_product_detalle_prodid')
                ->references('id')->on('registro_productividad_detalles')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_productividad_cantidads');
    }
};
