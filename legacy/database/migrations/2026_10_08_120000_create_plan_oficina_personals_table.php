<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Planilla oficina (régimen general): todos con contrato de oficina o general, en planilla (5ta categoría) o por
 * recibo por honorarios (4ta). Es la base del costo administrativo de Costos mensuales.
 *
 * - plan_contratos: cómo se le paga (planilla u honorarios, suspensión de 4ta, beneficios cada mes o en dos tramos) y
 *   a dónde (método, banco, cuenta principal y la secundaria donde se paga la diferencia).
 * - plan_mensuales: tasas del régimen general del mes y el Excel de la planilla oficina.
 * - configuracion / configuracion_historial: esas tasas, configurables por fecha en Planilla → Parámetros.
 * - plan_oficina_personals: una fila por persona y mes.
 */
return new class extends Migration {
    private const PARAMETROS = [
        'essalud_general' => ['9', 'EsSalud régimen general (planilla oficina), %'],
        'bonif_extraordinaria_general' => ['9', 'Bonificación extraordinaria sobre la gratificación, régimen general (Ley 30334), %'],
        'retencion_cuarta' => ['8', 'Retención de renta de 4ta categoría (recibo por honorarios), %'],
        'tope_retencion_cuarta' => ['1500', 'Monto del recibo por honorarios desde el que se retiene la 4ta categoría (S/)'],
    ];

    public function up(): void
    {
        Schema::table('plan_contratos', function (Blueprint $table) {
            // planilla: 5ta categoría (boleta); honorarios: 4ta categoría (recibo por honorarios)
            $table->string('tipo_ingreso', 20)->default('planilla')->after('tipo_planilla');
            $table->boolean('suspension_cuarta')->default(false)->after('tipo_ingreso');
            // CTS y gratificación: false = se retienen y se pagan en dos tramos (may/nov, jul/dic); true = cada mes con el sueldo
            $table->boolean('beneficios_mensuales')->default(false)->after('suspension_cuarta');
            $table->string('metodo_pago', 20)->nullable()->after('modalidad_pago');
            $table->string('banco', 40)->nullable()->after('metodo_pago');
            $table->string('tipo_cuenta', 20)->nullable()->after('banco');
            $table->string('moneda_cuenta', 3)->nullable()->after('tipo_cuenta');
            $table->string('numero_cuenta', 40)->nullable()->after('moneda_cuenta');
            // Cuenta donde se paga la diferencia (lo que no va por planilla)
            $table->string('banco_secundario', 40)->nullable()->after('numero_cuenta');
            $table->string('tipo_cuenta_secundaria', 20)->nullable()->after('banco_secundario');
            $table->string('moneda_cuenta_secundaria', 3)->nullable()->after('tipo_cuenta_secundaria');
            $table->string('numero_cuenta_secundaria', 40)->nullable()->after('moneda_cuenta_secundaria');
        });

        Schema::table('plan_mensuales', function (Blueprint $table) {
            $table->decimal('essalud_general', 5, 2)->nullable()->after('essalud');
            $table->decimal('bonif_extraordinaria_general', 5, 2)->nullable()->after('essalud_general');
            $table->decimal('retencion_cuarta', 5, 2)->nullable()->after('bonif_extraordinaria_general');
            $table->decimal('tope_retencion_cuarta', 10, 2)->nullable()->after('retencion_cuarta');
            $table->string('excel_oficina')->nullable()->after('excel');
        });

        foreach (self::PARAMETROS as $codigo => [$valor, $descripcion]) {
            DB::table('configuracion')->updateOrInsert(['codigo' => $codigo], ['valor' => $valor, 'descripcion' => $descripcion]);
            if (!DB::table('configuracion_historial')->where('configuracion_codigo', $codigo)->exists()) {
                DB::table('configuracion_historial')->insert([
                    'configuracion_codigo' => $codigo, 'valor' => $valor, 'fecha_inicio' => '2016-01-01', 'fecha_fin' => null,
                    'activo' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::create('plan_oficina_personals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_mensual_id')->constrained('plan_mensuales')->cascadeOnDelete();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados');
            $table->unsignedInteger('orden')->default(0);
            $table->string('nombres');
            $table->string('cargo')->nullable();

            // Del contrato del mes
            $table->string('tipo_ingreso', 20)->default('planilla');
            $table->boolean('beneficios_mensuales')->default(false);
            $table->string('sistema_pension', 20)->nullable();
            $table->boolean('es_pensionista')->default(false);
            $table->unsignedSmallInteger('edad')->nullable();
            $table->decimal('remuneracion_basica', 10, 2)->default(0);
            $table->decimal('asignacion_familiar', 10, 2)->default(0);
            $table->string('cuenta_principal')->nullable();
            $table->string('cuenta_secundaria')->nullable();
            $table->string('comprobante')->nullable(); // N° del recibo por honorarios del mes

            $table->unsignedTinyInteger('dias_mes')->default(30);
            $table->unsignedTinyInteger('dias_vacaciones')->default(0);
            $table->unsignedTinyInteger('dias_suspension_perfecta')->default(0);
            $table->unsignedTinyInteger('dias_laborados')->default(0);

            // Remuneraciones: sueldo por los días, vacaciones (0118), asignación familiar (0201); honorarios en rem_sueldo
            $table->decimal('rem_sueldo', 10, 2)->default(0);
            $table->decimal('rem_vacaciones', 10, 2)->default(0);
            $table->decimal('rem_asignacion_familiar', 10, 2)->default(0);
            $table->decimal('total_remuneracion', 10, 2)->default(0);
            // Descuentos
            $table->decimal('desc_afp_fondo', 10, 2)->default(0);
            $table->decimal('desc_afp_comision', 10, 2)->default(0);
            $table->decimal('desc_afp_prima', 10, 2)->default(0);
            $table->decimal('desc_snp', 10, 2)->default(0);
            $table->decimal('desc_renta_quinta', 10, 2)->default(0);
            $table->decimal('desc_renta_cuarta', 10, 2)->default(0);
            $table->decimal('total_descuentos', 10, 2)->default(0);
            $table->decimal('neto_planilla', 10, 2)->default(0);
            // Aportes del empleador
            $table->decimal('aporte_essalud', 10, 2)->default(0);
            $table->decimal('aporte_vida_ley', 10, 2)->default(0);
            // Beneficios: lo que corresponde al mes (provisión) y lo que se paga este mes
            $table->decimal('provision_cts', 10, 2)->default(0);
            $table->decimal('provision_gratificacion', 10, 2)->default(0);
            $table->decimal('gratificacion', 10, 2)->default(0);
            $table->decimal('bonif_extraordinaria', 10, 2)->default(0);
            $table->decimal('cts', 10, 2)->default(0);
            $table->decimal('beneficios_pagados', 10, 2)->default(0);
            // Real
            $table->decimal('sueldo_real', 10, 2)->nullable();
            $table->decimal('bonificacion_negro', 10, 2)->default(0);
            $table->decimal('pago_blanco_mes', 10, 2)->default(0);
            $table->decimal('costo_contable', 10, 2)->default(0);
            $table->decimal('costo_total', 10, 2)->default(0);

            $table->json('ajustes')->nullable();
            $table->json('calculados')->nullable();
            $table->timestamps();
            $table->unique(['plan_mensual_id', 'plan_empleado_id']);
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $permisos = [
            Permisos::PLANILLA_OFICINA => Permisos::PLANILLA_BLANCO,
            Permisos::PLANILLA_OFICINA_VER => Permisos::PLANILLA_BLANCO_VER,
            Permisos::PLANILLA_OFICINA_GESTIONAR => Permisos::PLANILLA_BLANCO_GESTIONAR,
        ];
        foreach ($permisos as $permiso => $_) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo(array_keys($permisos));
        // Quien ya gestiona la planilla agraria, también la de oficina
        foreach (Role::all() as $rol) {
            foreach ($permisos as $permiso => $agraria) {
                if ($rol->hasPermissionTo($agraria)) {
                    $rol->givePermissionTo($permiso);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_oficina_personals');
        foreach (array_keys(self::PARAMETROS) as $codigo) {
            DB::table('configuracion_historial')->where('configuracion_codigo', $codigo)->delete();
            DB::table('configuracion')->where('codigo', $codigo)->delete();
        }
        Schema::table('plan_mensuales', fn(Blueprint $table) => $table->dropColumn(['essalud_general', 'bonif_extraordinaria_general', 'retencion_cuarta', 'tope_retencion_cuarta', 'excel_oficina']));
        Schema::table('plan_contratos', fn(Blueprint $table) => $table->dropColumn([
            'tipo_ingreso', 'suspension_cuarta', 'beneficios_mensuales', 'metodo_pago', 'banco', 'tipo_cuenta', 'moneda_cuenta', 'numero_cuenta',
            'banco_secundario', 'tipo_cuenta_secundaria', 'moneda_cuenta_secundaria', 'numero_cuenta_secundaria',
        ]));
        Permission::whereIn('name', [Permisos::PLANILLA_OFICINA, Permisos::PLANILLA_OFICINA_VER, Permisos::PLANILLA_OFICINA_GESTIONAR])->delete();
    }
};
