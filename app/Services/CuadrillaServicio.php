<?php

namespace App\Services;

use App\Models\CuadrillaHora;

class CuadrillaServicio
{

    public function __construct()
    {

    }
    public static function cantidadCuadrilleros($fecha)
    {
        return CuadrillaHora::whereDate('fecha',$fecha)->count();
    }
}
