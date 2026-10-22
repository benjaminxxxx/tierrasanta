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
        Schema::table('plan_mensual_detalles', function (Blueprint $blueprint) {
            // Se agrega después del campo sueldo_neto (o el que prefieras)
            $blueprint->decimal('sueldo_negro_pagado', 12, 2)
                ->nullable()
                ->comment('Monto proporcional pactado a pagar al trabajador');
            $blueprint->decimal('total_horas', 12, 2)
                ->nullable();
            $blueprint->decimal('sueldo_blanco_pagado', 12, 2)
                ->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_mensual_detalles', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['sueldo_negro_pagado', 'total_horas','sueldo_blanco_pagado']);
        });
    }
};
