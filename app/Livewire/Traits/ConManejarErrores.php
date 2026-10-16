<?php

namespace App\Livewire\Traits;

use App\Services\Sistema\Error\SistemaErrorCrud;
use App\Services\Sistema\Error\SistemaErrorReglas;

/**
 * Forma anterior de mostrar errores. Ahora se apoya en el sistema de tickets (ver HandlesAlerts::errorAlert, que es lo
 * que se debe usar en código nuevo):
 * - error controlado (throw new Exception / ErrorControlado): se muestra su mensaje;
 * - error no controlado: se guarda el ticket y se muestra $mensajeUsuario con el número de ticket.
 */
trait ConManejarErrores
{
    public function manejarError(\Throwable $e, string $mensajeUsuario = 'Ocurrió un error inesperado')
    {
        if (SistemaErrorReglas::esControlado($e)) {
            $mensaje = $e->getMessage() ?: $mensajeUsuario;
        } else {
            $ticket = SistemaErrorCrud::registrar($e, static::class);
            $mensaje = $ticket
                ? "{$mensajeUsuario}. Quedó registrado con el ticket #{$ticket->id} para que lo revisen."
                : SistemaErrorReglas::mensajeUsuario(null);
        }

        if (method_exists($this, 'alert')) {
            $this->alert('error', $mensaje, [
                'position' => 'center', 'toast' => false, 'timer' => null,
                'showConfirmButton' => true, 'confirmButtonText' => 'Aceptar',
            ]);
        }
    }
}
