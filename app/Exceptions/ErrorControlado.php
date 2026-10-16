<?php

namespace App\Exceptions;

use Exception;

/**
 * Error conocido, con un mensaje pensado para el usuario ("No hay stock suficiente…"). errorAlert() lo muestra tal
 * cual y NO lo registra como ticket. Equivale a `throw new Exception('…')`, que también se toma como controlado;
 * usar esta clase deja más claro que el mensaje es intencional.
 */
class ErrorControlado extends Exception
{
}
