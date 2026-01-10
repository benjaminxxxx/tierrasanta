<?php

namespace App\Support;

use App\Services\Campo\Gestion\CampoServicio;
use Exception;

class ValidacionHelper
{
    public static function obtenerYValidarCampos(array $campos): array
    {

        $resultadoValidacion = CampoServicio::validarCamposDesdeExcel($campos);

        if (!empty($resultadoValidacion['invalidos'])) {
            throw new Exception("Los siguientes campos no existen en la base de datos: " . implode(', ', $resultadoValidacion['invalidos']));
        }

        return $resultadoValidacion['filtro'];
    }
}
