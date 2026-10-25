<?php

namespace App\Services\Sistema\Menu;

use App\Models\User;
use App\Models\UsuarioPreferencia;
use Illuminate\Support\Facades\Route;

/**
 * Menú que ve cada usuario y sus accesos directos.
 *  - Super Admin ve todo; Administrador todo menos EXCEPCIONES_ADMINISTRADOR; los demás roles necesitan el permiso de la
 *    opción y el de su módulo.
 *  - Los accesos directos son rutas del menú: solo se muestran las que el usuario puede ver.
 */
class SistemaMenuConsulta
{
    /** Permisos que Administrador NO ve (Super Admin sí). */
    public const EXCEPCIONES_ADMINISTRADOR = ['Auditoria Ver'];

    /**
     * Grupos con sus opciones visibles (con url, color e isActive); un grupo sin opciones visibles no aparece.
     */
    public static function menu(User $user, ?string $rutaActual = null): array
    {
        return collect(SistemaMenuReglas::grupos())
            ->map(function ($g) use ($user, $rutaActual) {
                $g['color'] = SistemaMenuReglas::COLORES[$g['clave']] ?? SistemaMenuReglas::COLORES['sistema'];
                $g['children'] = collect($g['children'])
                    ->filter(fn($op) => Route::has($op['route']) && self::puedeVer($user, $op))
                    ->map(fn($op) => $op + ['url' => route($op['route']), 'isActive' => $rutaActual === $op['route'], 'grupo' => $g['title'], 'color' => $g['color']])
                    ->values()->all();
                $g['isActive'] = collect($g['children'])->contains('isActive', true);
                return $g;
            })
            ->filter(fn($g) => $g['children'])
            ->values()->all();
    }

    /** @return array<string, array> ruta => opción visible (title, url, icon, color, grupo) */
    public static function opciones(User $user): array
    {
        return collect(self::menu($user))->flatMap(fn($g) => $g['children'])->keyBy('route')
            ->map(fn($o) => ['route' => $o['route'], 'title' => $o['title'], 'url' => $o['url'], 'icon' => $o['icon'], 'color' => $o['color'], 'grupo' => $o['grupo']])
            ->all();
    }

    /** Rutas de los accesos directos del usuario (las suyas o las de fábrica), solo las que puede ver, en orden. */
    public static function accesos(User $user, ?array $opciones = null): array
    {
        $opciones ??= self::opciones($user);
        $guardados = UsuarioPreferencia::where('user_id', $user->id)->value('accesos_directos');
        $rutas = $guardados === null ? SistemaMenuReglas::ACCESOS_INICIALES : (is_array($guardados) ? $guardados : json_decode($guardados, true));
        return array_values(array_filter((array) $rutas, fn($r) => isset($opciones[$r])));
    }

    /**
     * Cómo se abren los grupos del menú. Por defecto ninguno: todos cargan sin expandir.
     *
     * @return array{abrir_activo: bool, recordar_abiertos: bool}
     */
    public static function preferencias(User $user): array
    {
        $p = UsuarioPreferencia::where('user_id', $user->id)->first();
        return ['abrir_activo' => (bool) $p?->menu_abrir_activo, 'recordar_abiertos' => (bool) $p?->menu_recordar_abiertos];
    }

    public static function puedeVer(User $user, array $item): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        if ($user->hasRole('Administrador')) {
            return !isset($item['can']) || !array_intersect((array) $item['can'], self::EXCEPCIONES_ADMINISTRADOR);
        }
        if (!isset($item['can'])) {
            return false;
        }
        return $user->can($item['can']) && (!isset($item['modulo']) || $user->can($item['modulo']));
    }
}
