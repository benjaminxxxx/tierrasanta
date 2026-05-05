<?php
// app/Services/Sistema/PermisosServicio.php

namespace App\Services\Sistema;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisosServicio
{
    /**
     * Extrae todos los nombres de permisos del árbol (recursivo, sin importar profundidad).
     */
    public static function aplanarArbol(array $nodos): array
    {
        $nombres = [];
        foreach ($nodos as $nodo) {
            $nombres[] = $nodo['nombre'];
            if (!empty($nodo['hijos'])) {
                $nombres = array_merge($nombres, self::aplanarArbol($nodo['hijos']));
            }
        }
        return $nombres;
    }

    /**
     * Sincroniza el árbol con la tabla de permisos en BD:
     * - Crea los que no existen.
     * - Elimina los que ya no están en el árbol (limpieza opcional, comentar si no se quiere).
     */
    public static function sincronizarPermisosEnBD(array $arbol): void
    {
        $nombresEnArbol = self::aplanarArbol($arbol);

        foreach ($nombresEnArbol as $nombre) {
            Permission::firstOrCreate(
                ['name' => $nombre, 'guard_name' => 'web']
            );
        }

        // Opcional: eliminar permisos que ya no están en el árbol
        // Permission::whereNotIn('name', $nombresEnArbol)->delete();
    }

    /**
     * Guarda la relación entre un rol y sus permisos activados.
     * Compara el árbol completo con los activados y hace syncPermissions.
     */
    public static function guardarPermisosParaRol(string $rolNombre, array $permisosActivados): void
    {
        $rol = Role::findByName($rolNombre, 'web');
        $arbol = config('permisos_tree');

        // 1. Todos los nombres que DEBEN existir según el config actual
        $nombresEnArbol = self::aplanarArbol($arbol);

        // 2. Crear los permisos nuevos que no existen en BD
        foreach ($nombresEnArbol as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // 3. Eliminar permisos que ya no están en el config
        //    (los desvincula de todos los roles antes de borrar)
        Permission::whereNotIn('name', $nombresEnArbol)->each(function ($permiso) {
            // Spatie limpia role_has_permissions y model_has_permissions automáticamente
            $permiso->delete();
        });

        // 4. Sincronizar solo los activados para este rol
        //    syncPermissions recibe nombres y hace el diff automáticamente
        $rol->syncPermissions(
            array_intersect($permisosActivados, $nombresEnArbol)
        );
    }

    /**
     * Obtiene los nombres de permisos que tiene actualmente un rol.
     */
    public static function obtenerPermisosDeRol(string $rolNombre): array
    {
        $rol = Role::findByName($rolNombre, 'web');
        return $rol->permissions->pluck('name')->toArray();
    }
}