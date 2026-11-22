<?php

namespace App\Exceptions;

/** Se quiso quitar una siembra que tiene evaluaciones sin decidir qué pasa con ellas. */
class SiembraConEvaluaciones extends ErrorControlado
{
    /** @param array<int, array<string, mixed>> $evaluaciones las de EvaluacionSiembraConsulta::evaluaciones() */
    public function __construct(public array $evaluaciones)
    {
        parent::__construct('Esta siembra tiene ' . count($evaluaciones) . ' evaluación(es) vinculada(s): decide primero qué hacer con ellas.');
    }
}
