<?php

namespace App\Services;

use App\Models\Auditoria;

class AuditoriaServicio
{
    public static function getAuditoria(string $modelo, int $id): array
    {
        return Auditoria::where('modelo', $modelo)
            ->where('modelo_id', $id)
            ->orderByDesc('fecha_accion')
            ->get()
            ->map(fn($a) => [
                'accion' => $a->accion,
                'cambios' => is_string($a->cambios)
                    ? json_decode($a->cambios, true)
                    : $a->cambios,
                'observacion' => $a->observacion,
                'usuario_nombre' => $a->usuario_nombre,
                'fecha_accion' => $a->fecha_accion,
            ])
            ->toArray();
    }
    public static function registrar(
        string $modelo,
        int $modeloId,
        string $accion,
        ?array $antes = null,
        ?array $despues = null,
        ?string $observacion = null,
        array $camposIgnorados = []  // 👈 quien llama decide qué ignorar
    ): void {


        $usuario = auth()->user();

        $cambios = null;

        if ($accion === 'editar' && $antes && $despues) {
            $diff = self::diff($antes, $despues, $camposIgnorados);
            if (empty($diff))
                return;
            $cambios = $diff;
        } elseif ($accion === 'crear' && $despues) {
            $cambios = ['creado' => self::filtrar($despues, $camposIgnorados)];
        } elseif ($accion === 'eliminar' && $antes) {
            $cambios = ['eliminado' => self::filtrar($antes, $camposIgnorados)];
        }
        //array to string convertion si lo coloco aqui


        Auditoria::create([
            'modelo' => $modelo,
            'modelo_id' => $modeloId,
            'accion' => $accion,
            'cambios' => $cambios,
            'observacion' => $observacion,
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name,
            'fecha_accion' => now(),
        ]);
    }

    private static function diff(array $antes, array $despues, array $ignorados): array
    {
        $resultado = [];
        $claves = array_unique(array_merge(array_keys($antes), array_keys($despues)));

        foreach ($claves as $clave) {
            if (in_array($clave, $ignorados))
                continue;

            $valorAntes = $antes[$clave] ?? null;
            $valorDespues = $despues[$clave] ?? null;

            if ((string) $valorAntes !== (string) $valorDespues) {
                $resultado['antes'][$clave] = $valorAntes;
                $resultado['despues'][$clave] = $valorDespues;
            }
        }

        return $resultado;
    }

    private static function filtrar(array $datos, array $ignorados): array
    {
        return $ignorados
            ? array_diff_key($datos, array_flip($ignorados))
            : $datos;
    }
}