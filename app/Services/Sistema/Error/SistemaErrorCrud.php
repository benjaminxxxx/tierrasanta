<?php

namespace App\Services\Sistema\Error;

use App\Models\ErrorSistema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Guarda un error no controlado como ticket (tabla errores_sistema).
 *
 * Usa una conexión aparte: si el error ocurrió dentro de una transacción que luego se deshace, el ticket no se
 * pierde. Nunca lanza: si no se puede guardar, solo queda en el log.
 */
class SistemaErrorCrud
{
    private const CONEXION = 'errores_sistema';

    /** Errores ya registrados en esta petición (report() + errorAlert() no deben crear dos tickets). */
    private static ?\WeakMap $registrados = null;

    /** @param string|null $componente clase del componente Livewire que lo atrapó (si hay) */
    public static function registrar(Throwable $e, ?string $componente = null): ?ErrorSistema
    {
        if (SistemaErrorReglas::esControlado($e)) {
            return null;
        }
        self::$registrados ??= new \WeakMap();
        if (isset(self::$registrados[$e])) {
            return self::$registrados[$e];
        }
        try {
            self::prepararConexion();
            $request = request();
            $esLivewire = $request->routeIs('livewire.update') || $request->hasHeader('X-Livewire');
            // En Livewire la URL es /livewire/update: la pantalla real viene en el Referer
            $url = $esLivewire ? ($request->headers->get('referer') ?: $request->fullUrl()) : $request->fullUrl();

            $error = (new ErrorSistema())->setConnection(self::CONEXION);
            $error->fill([
                'huella' => SistemaErrorReglas::huella($e),
                'clase' => get_class($e),
                'mensaje' => mb_substr($e->getMessage(), 0, 60000),
                'archivo' => SistemaErrorReglas::rutaRelativa($e->getFile()),
                'linea' => $e->getLine(),
                'traza' => self::traza($e),
                'url' => mb_substr((string) $url, 0, 2048),
                'ruta' => self::rutaDeUrl($url) ?? $request->route()?->getName(),
                'metodo_http' => $request->method(),
                'componente' => $componente,
                'accion' => self::accion($e, $componente),
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'navegador' => mb_substr((string) $request->userAgent(), 0, 512),
                'estado' => 'pendiente',
            ])->save();
            return self::$registrados[$e] = $error;
        } catch (Throwable $falla) {
            Log::error('No se pudo registrar el ticket de error: ' . $falla->getMessage(), ['error_original' => $e->getMessage()]);
            return null;
        }
    }

    /** Conexión gemela de la principal (otra sesión de MySQL, fuera de cualquier transacción abierta). */
    private static function prepararConexion(): void
    {
        if (!config('database.connections.' . self::CONEXION)) {
            config(['database.connections.' . self::CONEXION => config('database.connections.' . config('database.default'))]);
        }
        DB::connection(self::CONEXION);
    }

    /** Traza legible: solo archivos del proyecto primero, rutas relativas, máximo 60 líneas. */
    private static function traza(Throwable $e): string
    {
        $lineas = [];
        foreach (array_slice($e->getTrace(), 0, 60) as $i => $f) {
            $donde = isset($f['file']) ? SistemaErrorReglas::rutaRelativa($f['file']) . ':' . ($f['line'] ?? '?') : '[interno]';
            $llamada = ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '') . '()';
            $lineas[] = "#{$i} {$donde} {$llamada}";
        }
        if ($e->getPrevious()) {
            $lineas[] = 'Causado por: ' . get_class($e->getPrevious()) . ': ' . mb_substr($e->getPrevious()->getMessage(), 0, 2000);
        }
        return implode("\n", $lineas);
    }

    /** Método donde se originó: el primero del componente, o la primera clase del proyecto en la traza. */
    private static function accion(Throwable $e, ?string $componente): ?string
    {
        $primeraDelProyecto = null;
        foreach ($e->getTrace() as $f) {
            $clase = $f['class'] ?? '';
            if ($componente && $clase === $componente) {
                return class_basename($clase) . '::' . $f['function'];
            }
            if (!$primeraDelProyecto && str_starts_with($clase, 'App\\')) {
                $primeraDelProyecto = class_basename($clase) . '::' . $f['function'];
            }
        }
        return $primeraDelProyecto;
    }

    private static function rutaDeUrl(?string $url): ?string
    {
        try {
            return $url ? app('router')->getRoutes()->match(request()->create($url))->getName() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
