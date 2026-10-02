<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Dos cajas que se cuadran cada mes:
 * - Caja de oficina (caja_oficina_movimientos): el dinero físico que pasa por la oficina. El dinero que
 *   nunca entra (una venta cobrada y gastada en campo) se registra con efecto cero: la fila y su inverso.
 * - Caja de movimientos (caja_movimientos): el flujo valorizado. Recibe los envíos de la caja de oficina
 *   y ahí se detalla lo que se gastó en campo.
 *
 * Son dos fuentes separadas: borrar o corregir en una no toca la otra; solo se anexan los envíos.
 */
return new class extends Migration {
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
            $table->string('importe_detalle', 255)->nullable();
            $table->decimal('tipo_cambio', 8, 4)->nullable();
            $table->boolean('es_saldo_inicial')->default(false)->comment('Saldo del año anterior: no es un ingreso');
            $table->foreignId('inverso_de_id')->nullable()->constrained('caja_oficina_movimientos')->nullOnDelete()
                ->comment('Fila que anula: el dinero no entró realmente a la caja');
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

        // Envío: lo que la caja de oficina manda a la de movimientos. Se acumulan hasta que se anexan.
        Schema::create('caja_oficina_envios', function (Blueprint $table) {
            $table->id();
            $table->enum('estado', ['pendiente', 'anexado'])->default('pendiente');
            $table->string('nota', 500)->nullable();
            $table->unsignedInteger('cambios')->default(0);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('enviado_nombre', 150)->nullable();
            $table->foreignId('anexado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anexado_nombre', 150)->nullable();
            $table->timestamp('anexado_at')->nullable();
            $table->timestamps();
            $table->index('estado');
        });

        Schema::create('caja_oficina_envio_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_oficina_envio_id')->constrained('caja_oficina_envios')->cascadeOnDelete();
            $table->unsignedBigInteger('caja_oficina_movimiento_id')->index();
            $table->enum('accion', ['nuevo', 'modificado', 'eliminado']);
            $table->json('datos')->comment('La fila de oficina al enviarla');
            $table->json('datos_antes')->nullable()->comment('Cómo se había enviado antes (modificado/eliminado)');
            $table->enum('resultado', ['anexado', 'omitido'])->nullable();
            $table->string('resultado_nota', 300)->nullable();
            $table->unsignedBigInteger('caja_movimiento_id')->nullable();
            $table->timestamps();
        });

        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->boolean('es_saldo_inicial')->default(false)->after('importe')->comment('Saldo del año anterior: no es un ingreso');
            $table->unsignedBigInteger('caja_oficina_movimiento_id')->nullable()->after('es_saldo_inicial')->index()
                ->comment('Fila de la caja de oficina de la que viene');
            $table->boolean('editado_manual')->default(false)->after('caja_oficina_movimiento_id')
                ->comment('Se editó aquí después de anexarla');
        });

        Schema::table('caja_fuentes', function (Blueprint $table) {
            $table->boolean('es_oficina')->default(false)->after('nombre')->comment('Dinero que está en la oficina (lo que lleva la caja de oficina)');
        });

        // El saldo del año anterior venía como un ingreso más del 02/01
        DB::table('caja_movimientos')->where('clasificador_1', 'like', 'SALDO%ANTERIOR%')->update(['es_saldo_inicial' => true]);
        DB::table('caja_fuentes')->where('nombre', 'AQP')->update(['es_oficina' => true]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $permisos = [Permisos::CAJA_OFICINA, Permisos::CAJA_OFICINA_VER, Permisos::CAJA_OFICINA_GESTIONAR];
        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }
        Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo($permisos);
    }

    public function down(): void
    {
        Schema::table('caja_fuentes', fn(Blueprint $table) => $table->dropColumn('es_oficina'));
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropColumn(['es_saldo_inicial', 'caja_oficina_movimiento_id', 'editado_manual']);
        });
        Schema::dropIfExists('caja_oficina_envio_detalles');
        Schema::dropIfExists('caja_oficina_envios');
        Schema::dropIfExists('caja_oficina_movimientos');
    }
};
