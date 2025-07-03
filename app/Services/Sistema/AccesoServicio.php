<?php

namespace App\Services\Sistema;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

class AccesoServicio
{
    public static function obtenerRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    public static function obtenerPermisos(): Collection
    {
        return Permission::all();
    }

    public static function crearPermiso(string $nombre, array $rolesIds = []): void
    {
        $permiso = Permission::firstOrCreate([
            'name' => $nombre,
            'guard_name' => 'web',
        ]);

        if (!empty($rolesIds)) {
            $roles = Role::whereIn('id', $rolesIds)->get();
            foreach ($roles as $rol) {
                $rol->givePermissionTo($permiso);
            }
        }
    }

    public static function actualizarPermiso(int $id, string $nombre, array $rolesIds = []): void
    {
        $permiso = Permission::findOrFail($id);
        $permiso->name = $nombre;
        $permiso->guard_name = 'web';
        $permiso->save();

        $rolesAsignados = Role::whereIn('id', $rolesIds)->get();
        foreach ($rolesAsignados as $rol) {
            if (!$rol->hasPermissionTo($permiso)) {
                $rol->givePermissionTo($permiso);
            }
        }

        $rolesRetirados = Role::whereNotIn('id', $rolesIds)->get();
        foreach ($rolesRetirados as $rol) {
            if ($rol->hasPermissionTo($permiso)) {
                $rol->revokePermissionTo($permiso);
            }
        }
    }

    public static function eliminarPermiso(int $id): void
    {
        $permiso = Permission::findOrFail($id);
        $permiso->roles()->detach();
        $permiso->delete();
    }

    public static function crearRol(string $nombre, array $permisosIds = []): void
    {
        $rol = Role::firstOrCreate([
            'name' => $nombre,
            'guard_name' => 'web',
        ]);

        if (!empty($permisosIds)) {
            $permisos = Permission::whereIn('id', $permisosIds)->get();
            $rol->syncPermissions($permisos);
        }
    }

    public static function actualizarRol(int $id, string $nombre, array $permisosIds = []): void
    {
        $rol = Role::findOrFail($id);
        $rol->name = $nombre;
        $rol->guard_name = 'web';
        $rol->save();

        $permisos = Permission::whereIn('id', $permisosIds)->get();
        $rol->syncPermissions($permisos);
    }

    public static function eliminarRol(int $id): void
    {
        $rol = Role::findOrFail($id);
        $rol->delete();
    }

    public static function obtenerRol(int $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public static function obtenerPermiso(int $id): Permission
    {
        return Permission::with('roles')->findOrFail($id);
    }
}
