<?php
namespace App\Traits;

use Throwable;

trait HandlesAlerts
{
    /**
     * Muestra una alerta de error personalizada utilizando el método $this->alert().
     */
    public function errorAlert($error, ?string $title = null, array $options = []): void
    {
        $mensaje = $error instanceof Throwable ? $error->getMessage() : (string) $error;

        if ($title) {
            $mensaje = "{$title}: {$mensaje}";
        }

        $configuracion = array_merge([
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'showConfirmButton' => true,
            'confirmButtonText' => 'Aceptar',
        ], $options);

        $this->alert('error', $mensaje, $configuracion);
    }

    /**
     * Muestra una alerta informativa utilizando SweetAlert / LivewireAlert.
     */
    public function infoAlert(string $mensaje, ?string $title = 'Información del Cálculo', array $options = []): void
    {
        if ($title) {
            $mensaje = "{$title}: {$mensaje}";
        }

        $configuracion = array_merge([
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'showConfirmButton' => true,
            'confirmButtonText' => 'Entendido'
        ], $options);

        $this->alert('info', $mensaje, $configuracion);
    }
}