<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelRegistro;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso a la API de los libros Excel (la usan los botones de la macro). Verifica, en este orden:
 * 1. que venga la clave (Authorization: Bearer …)            → 401 SIN_CLAVE
 * 2. que exista (no fue reemplazada ni borrada)              → 401 CLAVE_INVALIDA
 * 3. que no haya vencido                                     → 401 CLAVE_VENCIDA
 * 4. que el correo (X-Correo) sea el del dueño de la clave   → 401 CORREO_NO_COINCIDE
 * 5. que el usuario esté activo                              → 403 USUARIO_INACTIVO
 * 6. que la clave sea de este libro                          → 403 LIBRO_NO_AUTORIZADO
 * 7. que el usuario tenga hoy el permiso del libro           → 403 SIN_PERMISO
 * Las respuestas de error son texto: "ERROR:CODIGO" y en la línea siguiente el mensaje para el usuario.
 * Si todo está bien, la petición queda a nombre del usuario (auditoría, creado_por…).
 */
class LibroExcelApiAcceso
{
    public function handle(Request $request, Closure $next): Response
    {
        $libro = (string) $request->route('libro');
        if (!isset(SistemaLibroExcelRegistro::LIBROS[$libro])) {
            return self::error(404, 'LIBRO_NO_EXISTE', "No existe el libro \"{$libro}\" en este sistema.");
        }
        $plano = $request->bearerToken();
        if (!$plano) {
            return $this->rechazo($request, 401, 'SIN_CLAVE', 'Falta la clave de acceso. Descarga el Excel desde el sistema (trae tu clave).');
        }
        $token = PersonalAccessToken::findToken($plano);
        if (!$token) {
            return $this->rechazo($request, 401, 'CLAVE_INVALIDA', 'La clave de acceso de este archivo no es válida o fue reemplazada por una descarga más reciente. '
                . 'Usa el último archivo que descargaste o descárgalo de nuevo.');
        }
        if ($token->expires_at && $token->expires_at->isPast()) {
            return $this->rechazo($request, 401, 'CLAVE_VENCIDA', 'La clave de acceso de este archivo venció el ' . $token->expires_at->format('d/m/Y H:i')
                . '. Descarga el Excel de nuevo desde el sistema.');
        }
        $usuario = $token->tokenable;
        if (!$usuario instanceof User) {
            return $this->rechazo($request, 401, 'CLAVE_INVALIDA', 'La clave de acceso no pertenece a un usuario del sistema.');
        }
        $correo = mb_strtolower(trim((string) $request->header('X-Correo')));
        if ($correo === '' || $correo !== mb_strtolower($usuario->email)) {
            return $this->rechazo($request, 401, 'CORREO_NO_COINCIDE', 'El correo del archivo no corresponde a la clave de acceso. '
                . 'No modifiques la hoja de configuración; descarga el Excel de nuevo.');
        }
        if (isset($usuario->estado) && !$usuario->estado) {
            return $this->rechazo($request, 403, 'USUARIO_INACTIVO', "El usuario {$usuario->email} está desactivado. Habla con el administrador del sistema.");
        }
        if (!$token->can("libro:{$libro}")) {
            return $this->rechazo($request, 403, 'LIBRO_NO_AUTORIZADO', 'Esta clave de acceso es de otro libro. Descarga este Excel desde el sistema.');
        }
        $def = SistemaLibroExcelRegistro::definicion($libro);
        if (!$usuario->can($def->permiso())) {
            return $this->rechazo($request, 403, 'SIN_PERMISO', "Tu usuario ({$usuario->email}) no tiene el permiso \"{$def->permiso()}\" para "
                . mb_strtolower($def->reglas()::TITULO) . '. Pídelo al administrador del sistema.');
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($usuario);
        Auth::guard('web')->setUser($usuario);
        $request->attributes->set('libroDefinicion', $def);
        return $next($request);
    }

    /** Respuesta de error para la macro: "ERROR:CODIGO\nmensaje". */
    public static function error(int $estado, string $codigo, string $mensaje): Response
    {
        return response("ERROR:{$codigo}\n{$mensaje}", $estado, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** Error de autenticación / permiso: además queda en el log (ip, libro, código). */
    private function rechazo(Request $request, int $estado, string $codigo, string $mensaje): Response
    {
        Log::warning('API libros Excel: acceso rechazado', [
            'codigo' => $codigo, 'libro' => $request->route('libro'), 'ip' => $request->ip(), 'correo' => $request->header('X-Correo'),
        ]);
        return self::error($estado, $codigo, $mensaje);
    }
}
