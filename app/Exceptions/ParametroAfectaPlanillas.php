<?php

namespace App\Exceptions;

/**
 * Un cambio de parámetros de planilla cambiaría meses con planilla ya generada. No se guardó: la pantalla lo muestra
 * para que el usuario confirme; al confirmar se guarda y se regeneran esos meses.
 */
class ParametroAfectaPlanillas extends ErrorControlado
{
    /**
     * @param array<int, array{periodo:string, mes:int, anio:int, planillas:string[], cambios:string[]}> $meses
     */
    public function __construct(public readonly array $meses)
    {
        parent::__construct('Este cambio afecta planillas ya generadas: ' . implode(', ', array_column($meses, 'periodo'))
            . '. Confírmalo para guardar y regenerar esas planillas y su PLAME.');
    }
}
