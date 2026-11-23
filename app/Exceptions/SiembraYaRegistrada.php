<?php

namespace App\Exceptions;

use Illuminate\Support\Carbon;

/** La fecha de la siembra que se agrega ya existe como plantación del campo: una persona confirma si es la misma. */
class SiembraYaRegistrada extends ErrorControlado
{
    public function __construct(public string $fecha, public int $registros)
    {
        parent::__construct('El ' . Carbon::parse($fecha)->format('d/m/Y') . ' ya está registrada una plantación completa de este campo'
            . ($registros > 1 ? " ({$registros} veces)" : '') . '. ¿Es esta misma siembra?');
    }
}
