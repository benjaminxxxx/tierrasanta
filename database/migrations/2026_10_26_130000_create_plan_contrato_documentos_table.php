<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos de un contrato: por ahora el sustento del cese (carta de renuncia, acta o carta de despido). Contabilidad
 * da de baja en el T-Registro solo con ese documento. El archivo se guarda en el disco privado (local).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_contrato_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_contrato_id')->constrained('plan_contratos')->cascadeOnDelete();
            $table->string('tipo', 30)->comment('carta_renuncia | carta_despido | otro');
            $table->date('fecha_documento')->nullable()->comment('Fecha que figura en el documento');
            $table->string('nombre_original');
            $table->string('ruta')->comment('Ruta en el disco local (privado)');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('tamano')->nullable()->comment('bytes');
            $table->string('observacion')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['plan_contrato_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_contrato_documentos');
    }
};
