<?php

namespace App\Services\Insumo;

use App\Models\Auditoria;
use App\Models\Producto;
use App\Models\ProductoNutriente;
use App\Services\AuditoriaServicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InsumoServicio
{
    private const CAMPOS_IGNORADOS = ['creado_por', 'editado_por', 'eliminado_por', 'created_at', 'updated_at', 'deleted_at'];

    public static function guardar(array $data, array $nutrientes, array $usos, ?int $productoId = null): Producto
    {
        return DB::transaction(function () use ($data, $nutrientes, $usos, $productoId) {
            $usuarioId = Auth::id();

            if ($productoId) {
                $producto = Producto::findOrFail($productoId);
                $antes = $producto->toArray();

                $producto->update(array_merge($data, ['editado_por' => $usuarioId]));

                AuditoriaServicio::registrar(
                    modelo: Producto::class,
                    modeloId: $producto->id,
                    accion: 'editar',
                    antes: $antes,
                    despues: $producto->fresh()->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS,
                );
            } else {
                $producto = Producto::create(array_merge($data, ['creado_por' => $usuarioId]));

                AuditoriaServicio::registrar(
                    modelo: Producto::class,
                    modeloId: $producto->id,
                    accion: 'crear',
                    despues: $producto->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS,
                );
            }

            // Usos
            $producto->usos()->sync($usos);

            // Nutrientes
            ProductoNutriente::where('producto_id', $producto->id)->delete();

            if ($data['categoria_codigo'] === 'fertilizante') {
                foreach ($nutrientes as $codigo => $valor) {
                    if (!is_null($valor) && $valor != 0 && trim((string) $valor) !== '') {
                        ProductoNutriente::create([
                            'producto_id' => $producto->id,
                            'nutriente_codigo' => $codigo,
                            'porcentaje' => $valor,
                        ]);
                    }
                }
            }

            return $producto;
        });
    }

    public static function eliminar(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $usuarioId = Auth::id();

        $producto->update(['eliminado_por' => $usuarioId]);

        AuditoriaServicio::registrar(
            modelo: Producto::class,
            modeloId: $producto->id,
            accion: 'eliminar',
            antes: $producto->toArray(),
            camposIgnorados: self::CAMPOS_IGNORADOS,
        );

        $producto->delete();
    }

    public static function getAuditoria(int $id): array
    {
        return Auditoria::where('modelo', Producto::class)
            ->where('modelo_id', $id)
            ->orderByDesc('fecha_accion')
            ->get()
            ->map(fn($a) => [
                'accion' => $a->accion,
                'cambios' => is_string($a->cambios) ? json_decode($a->cambios, true) : $a->cambios,
                'observacion' => $a->observacion,
                'usuario_nombre' => $a->usuario_nombre,
                'fecha_accion' => $a->fecha_accion,
            ])
            ->toArray();
    }
    public static function listarProductos(
        string $search = null,
        string $categoriaCodigo = null,
        string $categoriaPesticida = null,
        string $usoId = null,
        array $nutrientes = [],
        string $sortField = 'nombre_comercial',
        string $sortDirection = 'asc',
        int $perPage = 20,
    ) {
        return Producto::with(['usos', 'kardexActual'])
            ->where(function ($q) use ($search) {
                $q->where('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('ingrediente_activo', 'like', "%{$search}%");
            })
            ->when($categoriaCodigo, fn($q) => $q->where('categoria_codigo', $categoriaCodigo))
            ->when($categoriaPesticida, fn($q) => $q->where('categoria_pesticida', $categoriaPesticida))
            ->when($usoId, fn($q) => $q->whereHas('usos', fn($q2) => $q2->where('ins_usos.id', $usoId)))
            ->when(!empty($nutrientes), function ($q) use ($nutrientes) {
                foreach ($nutrientes as $codigo) {
                    $q->whereHas('nutrientes', fn($q2) => $q2->where('nutriente_codigo', $codigo));
                }
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }
}