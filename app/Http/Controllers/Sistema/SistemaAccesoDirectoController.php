<?php

namespace App\Http\Controllers\Sistema;

use App\Exceptions\ErrorControlado;
use App\Http\Controllers\Controller;
use App\Services\Sistema\Menu\SistemaMenuProceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Accesos directos del menú lateral (los llama el menú con fetch, sin recargar la página).
 *  POST /sistema/accesos-directos  {accion: agregar|quitar|ordenar, ruta?: string, rutas?: string[]}
 *  POST /sistema/accesos-directos  {accion: preferencias, abrir_activo: bool, recordar_abiertos: bool}
 * Responde {accesos: string[]} / {preferencias: {...}} o {error: string} (422).
 */
class SistemaAccesoDirectoController extends Controller
{
    public function __invoke(Request $request, SistemaMenuProceso $proceso): JsonResponse
    {
        $datos = $request->validate([
            'accion' => 'required|in:agregar,quitar,ordenar,preferencias',
            'ruta' => 'required_if:accion,agregar,quitar|nullable|string|max:120',
            'abrir_activo' => 'boolean',
            'recordar_abiertos' => 'boolean',
            'rutas' => 'required_if:accion,ordenar|array',
            'rutas.*' => 'string|max:120',
        ]);
        try {
            if ($datos['accion'] === 'preferencias') {
                return response()->json(['preferencias' => $proceso->preferencias($request->user(),
                    (bool) ($datos['abrir_activo'] ?? false), (bool) ($datos['recordar_abiertos'] ?? false))]);
            }
            $accesos = match ($datos['accion']) {
                'agregar' => $proceso->agregar($request->user(), $datos['ruta']),
                'quitar' => $proceso->quitar($request->user(), $datos['ruta']),
                'ordenar' => $proceso->ordenar($request->user(), $datos['rutas']),
            };
            return response()->json(['accesos' => $accesos]);
        } catch (ErrorControlado $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
