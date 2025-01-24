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
        Schema::create('registro_productividad_bonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cuadrillero_id')->nullable()->constrained('cuadrilleros')->onDelete('cascade');
            $table->decimal('kg_adicional', 5, 2);
            $table->decimal('bono', 5, 2);

            $table->unsignedBigInteger('registro_productividad_id')->nullable();

            $table->foreign('registro_productividad_id', 'fk_reg_productividad_bono')
                ->references('id')->on('registro_productividads')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_productividad_bonos');
    }
};
