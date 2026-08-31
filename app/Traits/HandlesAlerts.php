<?php

namespace App\Traits;

use Throwable;

trait HandlesAlerts
{
    /**
     * Muestra una alerta de error personalizada utilizando el método $this->alert().
     *
     * @param Throwable|string $error Instancia de la excepción o mensaje de error.
     * @param string|null $title Título opcional para prefijar el mensaje.
     * @param array $options Opciones personalizadas para sobreescribir la configuración por defecto.
     * @return void
     */
    public function errorAlert($error, ?string $title = null, array $options = []): void
    {
        $mensaje = $error instanceof Throwable ? $error->getMessage() : (string) $error;

        if ($title) {
            $mensaje = "{$title}: {$mensaje}";
        }

        $configuracion = array_merge([
            'position'          => 'center',
            'toast'             => false,
            'timer'             => null,
            'showConfirmButton' => true,      // 👈 Muestra el botón
            'confirmButtonText' => 'Aceptar', // 👈 Texto del botón
        ], $options);

        $this->alert('error', $mensaje, $configuracion);
    }
}