<?php
namespace App\Services\Almacen;

use App\Models\Almacen;
use Exception;

class AlmacenPrincipalServicio
{
    /**
     * Obtiene el almacén principal o lanza una excepción si no existe.
     *
     * @throws Exception
     */
    public static function obtenerAlmacenPrincipal(): Almacen
    {
        $almacen = Almacen::where('es_principal', true)
            ->where('activo', true)
            ->first();

        if (!$almacen) {
            throw new Exception('No hay un almacén principal configurado en el sistema.');
        }

        return $almacen;
    }
}