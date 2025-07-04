<?php

namespace App\Services\Campo;

use App\Models\Actividad;
use App\Models\Labores;

class ActividadesServicio
{
    public static function obtenerLabores(){
        return Labores::all();
    }
}