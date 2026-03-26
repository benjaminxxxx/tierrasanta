<?php

namespace App\Services\Insumo;

use App\Models\Auditoria;
use App\Models\InsUso;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InsumoUsoServicio
{
    public static function getUsos(): array
    {
        return InsUso::withCount('productos')
            ->get()
            ->map(fn($u) => [
                'id'               => $u->id,
                'nombre'           => $u->nombre,
                'categoria_codigo' => $u->categoria_codigo,
                'descripcion'      => $u->descripcion,
                'activo'           => $u->activo,
                'productos_count'  => $u->productos_count,
                'created_at'       => $u->created_at?->format('d/m/Y H:i'),
                'updated_at'       => $u->updated_at?->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    public static function guardarUso(array $fila): InsUso
    {
        $nombre          = trim($fila['nombre'] ?? '');
        $categoriaCodigo = $fila['categoria_codigo'] ?? null;
        $usuarioId       = Auth::id();

        if (!$nombre) {
            throw ValidationException::withMessages([
                'nombre' => 'El nombre es obligatorio.',
            ]);
        }

        $duplicado = InsUso::where('nombre', $nombre)
            ->where('categoria_codigo', $categoriaCodigo)
            ->when(!empty($fila['id']), fn($q) => $q->where('id', '!=', $fila['id']))
            ->exists();

        if ($duplicado) {
            $categoria = $categoriaCodigo ?? 'sin categoría';
            throw ValidationException::withMessages([
                'nombre' => "'{$nombre}' ({$categoria}) ya existe.",
            ]);
        }

        if (!empty($fila['id'])) {
            return self::actualizar($fila, $nombre, $categoriaCodigo, $usuarioId);
        }

        return self::crear($fila, $nombre, $categoriaCodigo, $usuarioId);
    }

    public static function eliminarUso(int $id): void
    {
        $uso = InsUso::findOrFail($id);

        Auditoria::create([
            'modelo'         => InsUso::class,
            'modelo_id'      => $uso->id,
            'accion'         => 'eliminar',
            'cambios'        => json_encode(['eliminado' => $uso->toArray()]),
            'usuario_id'     => Auth::id(),
            'usuario_nombre' => Auth::user()->name,
            'fecha_accion'   => now(),
        ]);

        $uso->delete();
    }

    public static function getAuditoria(int $id): array
    {
        return Auditoria::where('modelo', InsUso::class)
            ->where('modelo_id', $id)
            ->orderByDesc('fecha_accion')
            ->get()
            ->map(fn($a) => [
                'accion'         => $a->accion,
                'cambios'        => is_string($a->cambios) ? json_decode($a->cambios, true) : $a->cambios,
                'observacion'    => $a->observacion,
                'usuario_nombre' => $a->usuario_nombre,
                'fecha_accion'   => $a->fecha_accion,
            ])
            ->toArray();
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private static function crear(array $fila, string $nombre, ?string $categoriaCodigo, int $usuarioId): InsUso
    {
        $uso = InsUso::create([
            'nombre'           => $nombre,
            'categoria_codigo' => $categoriaCodigo,
            'descripcion'      => $fila['descripcion'] ?? null,
            'activo'           => $fila['activo'] ?? true,
            'creado_por'       => $usuarioId,
        ]);

        Auditoria::create([
            'modelo'         => InsUso::class,
            'modelo_id'      => $uso->id,
            'accion'         => 'crear',
            'cambios'        => json_encode(['creado' => $uso->toArray()]),
            'usuario_id'     => $usuarioId,
            'usuario_nombre' => Auth::user()->name,
            'fecha_accion'   => now(),
        ]);

        return $uso;
    }

    private static function actualizar(array $fila, string $nombre, ?string $categoriaCodigo, int $usuarioId): InsUso
    {
        $uso   = InsUso::findOrFail($fila['id']);
        $antes = $uso->only(['nombre', 'categoria_codigo', 'descripcion', 'activo']);

        $uso->update([
            'nombre'           => $nombre,
            'categoria_codigo' => $categoriaCodigo,
            'descripcion'      => $fila['descripcion'] ?? null,
            'activo'           => $fila['activo'] ?? true,
            'editado_por'      => $usuarioId,
        ]);

        $despues = $uso->fresh()->only(['nombre', 'categoria_codigo', 'descripcion', 'activo']);

        if ($antes !== $despues) {
            Auditoria::create([
                'modelo'         => InsUso::class,
                'modelo_id'      => $uso->id,
                'accion'         => 'editar',
                'cambios'        => json_encode(['antes' => $antes, 'despues' => $despues]),
                'usuario_id'     => $usuarioId,
                'usuario_nombre' => Auth::user()->name,
                'fecha_accion'   => now(),
            ]);
        }

        return $uso;
    }
}