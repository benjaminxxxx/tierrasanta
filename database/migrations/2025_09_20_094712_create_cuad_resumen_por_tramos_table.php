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
        Schema::create('cuad_resumen_tramos', function (Blueprint $table) {
            $table->id();

            // Datos principales
            $table->string('grupo_codigo'); // se guarda como string para mantener historial
            $table->string('color', 10)->nullable(); // formato corto tipo #FFFFFF
            $table->enum('tipo', ['adicional','sueldo','bono']);
            $table->string('descripcion', 255);
            $table->enum('condicion', ['Pendiente', 'Pagado'])->default('Pendiente');
            $table->date('fecha')->nullable();
            $table->string('recibo')->nullable();
            $table->integer('orden')->nullable();
            $table->enum('modalidad_pago',['quincenal','mensual','semanal']);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_acumulada')->nullable();
            $table->string('excel_reporte_file')->nullable();
            // Montos
            $table->decimal('deuda_actual', 12, 2)->default(0);
            $table->decimal('deuda_acumulada', 12, 2)->default(0);

            // Relaciones
            $table->foreignId('tramo_id')
                ->constrained('cuad_tramos_laborales')
                ->onDelete('cascade');

            $table->unsignedBigInteger('tramo_acumulado_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_resumen_tramos');
    }
};
