<?php

namespace App\Services\Sistema\Error;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Qué error es "controlado" (mensaje intencional para el usuario: no se guarda) y cuál no (error de código o de base
 * de datos: se guarda como ticket).
 *
 * Controlado: `throw new Exception('…')` (exactamente esa clase, es lo que usa todo el sistema para avisos),
 * App\Exceptions\ErrorControlado y cualquier excepción propia de App\Exceptions, validaciones y permisos.
 * No controlado: todo lo demás (QueryException, TypeError, ErrorException, ModelNotFoundException…).
 */
class SistemaErrorReglas
{
    public static function esControlado(Throwable $e): bool
    {
        return get_class($e) === Exception::class
            || str_starts_with(get_class($e), 'App\\Exceptions\\')
            || $e instanceof ValidationException
            || $e instanceof AuthorizationException;
    }

    /** Mismo error (clase + lugar), para contar cuántas veces pasó. */
    public static function huella(Throwable $e): string
    {
        return md5(get_class($e) . '|' . self::rutaRelativa($e->getFile()) . '|' . $e->getLine());
    }

    public static function rutaRelativa(?string $archivo): ?string
    {
        return $archivo ? ltrim(str_replace([base_path(), '\\'], ['', '/'], $archivo), '/') : null;
    }

    /** Mensaje para el usuario cuando el error no es controlado. */
    public static function mensajeUsuario(?int $ticket): string
    {
        return $ticket
            ? "Ocurrió un error inesperado. Quedó registrado con el ticket #{$ticket} para que lo revisen; no hace falta enviar captura. Si te urge, menciona ese número."
            : 'Ocurrió un error inesperado. Avisa al administrador indicando qué estabas haciendo.';
    }
}
