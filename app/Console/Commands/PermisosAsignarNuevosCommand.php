<?php

namespace App\Console\Commands;

use App\Constants\Permisos;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Da los permisos nuevos a los roles que ya tenían el permiso equivalente anterior, para que nadie pierda lo que hacía
 * (se puede repetir; en el servidor antiguo, después de migrar):
 *   - Cargos, Remuneraciones, Pensiones y Cuentas de pago de empleados ← "Gestionar Contratos y Sueldos".
 *   - Producción de campo (ver) ← Bonificaciones de cuadrilla o Ingreso de cochinilla.
 *   - Producción de campo (registrar sin bono) ← Actualizar bonificaciones.
 * Super Admin ya tiene todos. php artisan permisos:asignar-nuevos --simular
 */
class PermisosAsignarNuevosCommand extends Command
{
    protected $signature = 'permisos:asignar-nuevos {--simular : Solo muestra qué se asignaría}';

    protected $description = 'Asigna los permisos nuevos a los roles que tenían el permiso equivalente anterior';

    /** permiso nuevo => permisos anteriores (basta tener uno) */
    public const HERENCIA = [
        Permisos::PERSONAL_CARGOS => [Permisos::PERSONAL_CONTRATOS],
        Permisos::PERSONAL_REMUNERACIONES => [Permisos::PERSONAL_CONTRATOS],
        Permisos::PERSONAL_PENSIONES => [Permisos::PERSONAL_CONTRATOS],
        Permisos::PERSONAL_PAGOS => [Permisos::PERSONAL_CONTRATOS],
        Permisos::CAMPO_PRODUCCION => [Permisos::CUADRILLA_BONIFICACION, Permisos::COCHINILLA_INGRESO],
        Permisos::CAMPO_PRODUCCION_REGISTRAR => [Permisos::CUADRILLA_BONIFICACION_ACTUALIZAR],
    ];

    public function handle(): int
    {
        $simular = (bool) $this->option('simular');
        $filas = [];
        foreach (self::HERENCIA as $nuevo => $anteriores) {
            if (!Permission::where('name', $nuevo)->exists()) {
                $this->warn("El permiso \"{$nuevo}\" no existe: ejecuta antes php artisan db:seed --class=PermisosSeeder");
                continue;
            }
            $roles = Role::whereHas('permissions', fn($q) => $q->whereIn('name', $anteriores))->where('name', '<>', 'Super Admin')->get();
            foreach ($roles as $rol) {
                if ($rol->hasPermissionTo($nuevo)) {
                    continue;
                }
                $filas[] = [$rol->name, $nuevo, 'porque tiene: ' . implode(' / ', array_filter($anteriores, fn($a) => $rol->hasPermissionTo($a)))];
                if (!$simular) {
                    $rol->givePermissionTo($nuevo);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $filas ? $this->table(['Rol', 'Permiso nuevo', 'Motivo'], $filas) : $this->line('No hay nada que asignar.');
        $this->info($simular ? 'Simulación: no se asignó nada.' : 'Listo: ' . count($filas) . ' asignación(es).');
        return self::SUCCESS;
    }
}
