<?php

namespace App\Services\Sistema\Menu;

use App\Exceptions\ErrorControlado;
use App\Models\User;
use App\Models\UsuarioPreferencia;

/**
 * Accesos directos del menú de cada usuario: agregar (clic derecho → "Agregar a acceso directo"), quitar (ícono de pin)
 * y ordenar. La primera vez que se cambian, se parte de los de fábrica.
 */
class SistemaMenuProceso
{
    /** @return string[] accesos resultantes */
    public function agregar(User $user, string $ruta): array
    {
        $opciones = SistemaMenuConsulta::opciones($user);
        if (!isset($opciones[$ruta])) {
            throw new ErrorControlado('Esa opción no está en tu menú.');
        }
        $accesos = SistemaMenuConsulta::accesos($user, $opciones);
        if (!in_array($ruta, $accesos, true)) {
            if (count($accesos) >= SistemaMenuReglas::MAX_ACCESOS) {
                throw new ErrorControlado('Puedes tener hasta ' . SistemaMenuReglas::MAX_ACCESOS . ' accesos directos: quita alguno primero.');
            }
            $accesos[] = $ruta;
        }
        return $this->guardar($user, $accesos);
    }

    public function quitar(User $user, string $ruta): array
    {
        return $this->guardar($user, array_values(array_diff(SistemaMenuConsulta::accesos($user), [$ruta])));
    }

    /** @param string[] $rutas nuevo orden (las que no están en el menú del usuario se ignoran) */
    public function ordenar(User $user, array $rutas): array
    {
        $actuales = SistemaMenuConsulta::accesos($user);
        $orden = array_values(array_intersect(array_unique($rutas), $actuales));
        return $this->guardar($user, array_merge($orden, array_values(array_diff($actuales, $orden))));
    }

    /** Preferencias de apertura de los grupos del menú. @return array{abrir_activo: bool, recordar_abiertos: bool} */
    public function preferencias(User $user, bool $abrirActivo, bool $recordarAbiertos): array
    {
        UsuarioPreferencia::updateOrCreate(['user_id' => $user->id], ['menu_abrir_activo' => $abrirActivo, 'menu_recordar_abiertos' => $recordarAbiertos]);
        return SistemaMenuConsulta::preferencias($user);
    }

    private function guardar(User $user, array $accesos): array
    {
        UsuarioPreferencia::updateOrCreate(['user_id' => $user->id], ['accesos_directos' => array_values($accesos)]);
        return $accesos;
    }
}
