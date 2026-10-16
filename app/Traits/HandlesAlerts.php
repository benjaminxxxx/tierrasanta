<?php
namespace App\Traits;

use App\Services\Sistema\Error\SistemaErrorCrud;
use App\Services\Sistema\Error\SistemaErrorReglas;
use Throwable;

trait HandlesAlerts
{
    /**
     * Alerta de éxito centrada, sin temporizador y que no se cierra sola (para resultados largos que hay que leer).
     * Para avisos rápidos que se cierran solos, usar $this->alert('success', ...).
     */
    public function successAlert($mensaje, array $options = []): void
    {
        $configuracion = array_merge([
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'showConfirmButton' => true,
            'confirmButtonText' => 'Aceptar',
        ], $options);

        $this->alert('success', $mensaje, $configuracion);
    }
    /**
     * Muestra un error al usuario. Usar en el catch de toda acción de botón: catch (\Throwable $e) { $this->errorAlert($e); }
     *
     * - Error controlado (throw new Exception('…') o App\Exceptions\ErrorControlado): muestra el mensaje tal cual.
     * - Error no controlado (base de datos, código…): lo guarda como ticket en errores_sistema (hora, pantalla,
     *   usuario, traza) y muestra un mensaje entendible con el número de ticket. Quien tiene permiso de ver errores
     *   también ve el detalle técnico.
     */
    public function errorAlert($error, ?string $title = null, array $options = []): void
    {
        $mensaje = $error instanceof Throwable ? $error->getMessage() : (string) $error;

        if ($error instanceof Throwable && !SistemaErrorReglas::esControlado($error)) {
            $ticket = SistemaErrorCrud::registrar($error, static::class);
            $mensaje = SistemaErrorReglas::mensajeUsuario($ticket?->id);
            if (auth()->user()?->can(\App\Constants\Permisos::SISTEMA_ERRORES)) {
                $mensaje .= "\n\nDetalle técnico: " . mb_strimwidth($error->getMessage(), 0, 600, '…');
            }
        }

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