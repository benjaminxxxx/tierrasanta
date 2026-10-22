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
        Schema::create('ins_kardex_reporte_categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('ins_kardex_reportes')->onDelete('cascade');
            $table->string('categoria_codigo', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_reporte_categorias');
    }
};
