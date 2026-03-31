<?php

namespace App\Services\Categoria;

use App\Models\InsSubcategoria;
use App\Models\Auditoria;
use App\Services\AuditoriaServicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubcategoriaServicio
{
    private const CAMPOS_IGNORADOS = [
        'creado_por',
        'editado_por',
        'eliminado_por',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function guardar(array $data, ?int $subcategoriaId = null): InsSubcategoria
    {
        $rules = [
            'categoria_codigo' => ['required', 'string', 'exists:ins_categorias,codigo'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                // Unique compuesto (categoria_codigo + nombre) ignorando el registro actual en edición
                Rule::unique('ins_subcategorias')
                    ->where(
                        fn($q) => $q
                            ->where('categoria_codigo', $data['categoria_codigo'])
                            ->whereNull('deleted_at')
                    )
                    ->ignore($subcategoriaId),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];

        $messages = [
            'categoria_codigo.required' => 'La categoría es obligatoria.',
            'categoria_codigo.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 255 caracteres.',
            'nombre.unique' => 'Ya existe una subcategoría con ese nombre en esta categoría.',
            'descripcion.max' => 'La descripción no puede superar 500 caracteres.',
        ];

        Validator::make($data, $rules, $messages)->validate();

        return DB::transaction(function () use ($data, $subcategoriaId) {
            $usuarioId = Auth::id();

            if ($subcategoriaId) {
                $subcategoria = InsSubcategoria::findOrFail($subcategoriaId);
                $antes = $subcategoria->toArray();

                $subcategoria->update(array_merge($data, ['editado_por' => $usuarioId]));

                AuditoriaServicio::registrar(
                    modelo: InsSubcategoria::class,
                    modeloId: $subcategoria->id,
                    accion: 'editar',
                    antes: $antes,
                    despues: $subcategoria->fresh()->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS,
                );
            } else {
                $subcategoria = InsSubcategoria::create(
                    array_merge($data, ['creado_por' => $usuarioId])
                );

                AuditoriaServicio::registrar(
                    modelo: InsSubcategoria::class,
                    modeloId: $subcategoria->id,
                    accion: 'crear',
                    despues: $subcategoria->toArray(),
                    camposIgnorados: self::CAMPOS_IGNORADOS,
                );
            }

            return $subcategoria;
        });
    }

    public static function eliminar(int $id): void
    {
        $subcategoria = InsSubcategoria::findOrFail($id);
        $usuarioId = Auth::id();

        $subcategoria->update(['eliminado_por' => $usuarioId]);

        AuditoriaServicio::registrar(
            modelo: InsSubcategoria::class,
            modeloId: $subcategoria->id,
            accion: 'eliminar',
            antes: $subcategoria->toArray(),
            camposIgnorados: self::CAMPOS_IGNORADOS,
        );

        $subcategoria->delete();
    }

    public static function getAuditoria(int $id): array
    {
        return Auditoria::where('modelo', InsSubcategoria::class)
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

    public static function listar(
        ?string $search = null,
        ?string $categoriaCodigo = null,
        string $sortField = 'nombre',
        string $sortDirection = 'asc',
        int $perPage = 50,
    ) {
        return InsSubcategoria::with('categoria')
            ->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->when($categoriaCodigo, fn($q) => $q->where('categoria_codigo', $categoriaCodigo))
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }
}