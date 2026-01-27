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
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            // Usamos decimal para precisión financiera
            $table->decimal('jornal_aplicado', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropColumn('jornal_aplicado');
        });
    }
};
