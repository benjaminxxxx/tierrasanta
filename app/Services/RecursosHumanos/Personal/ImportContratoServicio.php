<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Support\ExcelHelper;


class ImportContratoServicio
{
    public function importarContratos($file)
    {
        $hojas = [
            'CONTRATOS' => 'tblContratos',
        ];
        $data = ExcelHelper::cargarData($file, $hojas);

        return $data['CONTRATOS'];
        /*
        array:1 [▼ // app\Services\RecursosHumanos\Personal\ImportContratoServicio.php:16
            "CONTRATOS" => array:252 [▼
                0 => array:10 [▼
                "n°" => 1
                "planilla" => "AGRARIA"
                "materno" => "APAZA"
                "paterno" => "TITO"
                "nombres" => "PATRICIA ROXANA"
                "dni" => "29683489"
                "sistema" => "Profuturo"
                "fecha_ingreso" => 42373
                "estado" => "Continúa"
                "fecha_baja" => null
                ]*/
    }

}