<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_oficina_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 150);
            $table->unsignedInteger('numero_caja')->nullable();
            $table->boolean('es_contable')->default(false);
            $table->enum('condicion', ['NEG', 'BLA'])->default('NEG');
            $table->string('categoria', 100)->nullable();
            $table->string('codigo', 100)->nullable();
            $table->string('beneficiario', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('moneda', 3)->default('PEN');
            $table->date('fecha');
            $table->unsignedTinyInteger('semana');
            $table->text('tipo_documento')->nullable();
            $table->string('numero_documento', 200)->nullable();
            $table->string('situacion_cheque', 150)->nullable();
            $table->decimal('importe_usd', 14, 2)->nullable();
            $table->decimal('tipo_cambio_operacion', 8, 4)->nullable();
            $table->decimal('importe', 14, 2)->comment('Soles: + ingreso, − egreso');
            $table->string('importe_detalle')->nullable();
            $table->decimal('tipo_cambio', 8, 4)->nullable();
            $table->boolean('es_saldo_inicial')->default(false)->comment('Saldo del año anterior: no es un ingreso');
            $table->foreignId('inverso_de_id')->nullable()->constrained('caja_oficina_movimientos')->nullOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('pendiente_envio')->default(true)->comment('Cambió y aún no se envía a la caja de movimientos');
            $table->timestamp('enviado_at')->nullable();
            $table->json('ultimo_enviado')->nullable()->comment('Datos tal como se enviaron la última vez');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_eliminacion', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fecha', 'orden']);
            $table->index('pendiente_envio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_oficina_movimientos');
    }
};
