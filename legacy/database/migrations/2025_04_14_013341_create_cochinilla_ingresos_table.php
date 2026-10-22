<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCochinillaIngresosTable extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_ingresos', function (Blueprint $table) {
            $table->id();
            $table->integer('lote')->unique();
            $table->date('fecha');
            $table->string('campo');
            $table->decimal('area', 8, 3)->nullable();
            $table->unsignedBigInteger('campo_campania_id')->nullable();
        
            $table->string('observacion')->nullable(); // ahora será clave foránea
            $table->decimal('proveedor_kg_exportado', 8, 2)->nullable();
            $table->decimal('kg_ha', 8, 2)->nullable();

            $table->decimal('total_kilos', 8, 2)->nullable();
        
            $table->decimal('diferencia_kilos', 8, 2)->default(0);
            $table->decimal('porcentaje_diferencia', 5, 2)->default(0);
        
            $table->decimal('venteado_kilos_ingresados', 8, 2)->default(0);
            $table->decimal('venteado_limpia', 8, 2)->default(0);
            $table->decimal('venteado_basura', 8, 2)->default(0);
            $table->decimal('venteado_polvillo', 8, 2)->default(0);
            $table->decimal('venteado_limpia_porcentaje', 5, 2)->default(0);
            $table->decimal('venteado_basura_porcentaje', 5, 2)->default(0);
            $table->decimal('venteado_polvillo_porcentaje', 5, 2)->default(0);
            $table->decimal('venteado_diferencia_kilos', 8, 2)->default(0);
            $table->decimal('venteado_diferencia_porcentaje', 5, 2)->default(0);
        
            $table->decimal('filtrado_kilos_ingresados', 8, 2)->default(0);
            $table->decimal('filtrado_primera', 8, 2)->default(0);
            $table->decimal('filtrado_segunda', 8, 2)->default(0);
            $table->decimal('filtrado_tercera', 8, 2)->default(0);
            $table->decimal('filtrado_piedra', 8, 2)->default(0);
            $table->decimal('filtrado_basura', 8, 2)->default(0);
            $table->decimal('filtrado_primera_porcentaje', 5, 2)->default(0);
            $table->decimal('filtrado_segunda_porcentaje', 5, 2)->default(0);
            $table->decimal('filtrado_tercera_porcentaje', 5, 2)->default(0);
            $table->decimal('filtrado_piedra_porcentaje', 5, 2)->default(0);
            $table->decimal('filtrado_basura_porcentaje', 5, 2)->default(0);
            $table->decimal('filtrado_diferencia_kilos', 8, 2)->default(0);
            $table->decimal('filtrado_diferencia_porcentaje', 5, 2)->default(0);
            
            $table->decimal('stock_disponible', 10, 2)->nullable();
            $table->timestamps();
        
            $table->foreign('campo_campania_id')->references('id')->on('campos_campanias')->onDelete('set null');
            $table->foreign('observacion')->references('codigo')->on('cochinilla_observaciones')->onDelete('set null');
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_ingresos');
    }
}
