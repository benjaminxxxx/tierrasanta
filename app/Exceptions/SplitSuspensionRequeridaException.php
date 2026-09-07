<?php

namespace App\Exceptions;

use App\Models\PlanSuspension;
use Exception;

class SplitSuspensionRequeridaException extends Exception
{
    public PlanSuspension $suspension;
    public string $fecha;

    public function __construct(PlanSuspension $suspension, string $fecha)
    {
        $this->suspension = $suspension;
        $this->fecha = $fecha;

        parent::__construct(
            "Retirar el {$fecha} de la suspensión #{$suspension->id} " .
            "({$suspension->fecha_inicio} al {$suspension->fecha_fin}) requiere partirla en dos tramos, " .
            "porque esa fecha está en medio del rango, no en un extremo."
        );
    }
}