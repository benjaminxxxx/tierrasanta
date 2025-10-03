<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Log;

trait ConManejarErrores
{
    /**
     * Manejar excepciones y mostrar alertas amigables en Livewire.
     */
    public function manejarError(\Throwable $e, string $mensajeUsuario = 'Ocurrió un error inesperado')
    {
        // Registrar en log con fecha y hora
        Log::error(sprintf(
            "[%s] %s (archivo: %s, línea: %d)",
            now()->format('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        // Mostrar alerta amigable en frontend
        if (method_exists($this, 'alert')) {
            $this->alert('error', $mensajeUsuario);
        }
    }
}