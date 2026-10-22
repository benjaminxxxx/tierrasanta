<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costo_fdm_mensuals', function (Blueprint $table) {
            $table->id();
            $table->string('destinatario');
            $table->text('descripcion');
            $table->decimal('monto_blanco', 14, 6);
            $table->decimal('monto_negro', 14, 6);
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costo_fdm_mensuals');
    }
};
