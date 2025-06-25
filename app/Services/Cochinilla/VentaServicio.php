<?php

namespace App\Services\Cochinilla;

use App\Models\VentaCochinilla;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class VentaServicio
{
    public static function listar()
    {
        $query = VentaCochinilla::query();
        return $query;
    }
    public static function listarParaEntregadorPaginado($filtros = [], $perPage = 5)
    {
        $queryGrupos = VentaCochinilla::query();

        // Filtros por grupo que contenga al menos un registro coincidente
        if (!empty($filtros['campo'])) {
            $queryGrupos->where('campo', 'like', '%' . $filtros['campo'] . '%');
        }

        if (!empty($filtros['cliente'])) {
            $queryGrupos->where('cliente', 'like', '%' . $filtros['cliente'] . '%');
        }

        if (!empty($filtros['condicion'])) {
            $queryGrupos->where('condicion', $filtros['condicion']);
        }

        if (!empty($filtros['fecha_filtrado'])) {
            $queryGrupos->whereDate('fecha_filtrado', $filtros['fecha_filtrado']);
        }

        if (!empty($filtros['anio_filtrado'])) {
            $queryGrupos->whereYear('fecha_filtrado', $filtros['anio_filtrado']);
        }

        if (!empty($filtros['mes_filtrado'])) {
            $queryGrupos->whereMonth('fecha_filtrado', $filtros['mes_filtrado']);
        }

        if (!empty($filtros['fecha_venta'])) {
            $queryGrupos->whereDate('fecha_venta', $filtros['fecha_venta']);
        }

        if (!empty($filtros['anio_venta'])) {
            $queryGrupos->whereYear('fecha_venta', $filtros['anio_venta']);
        }

        if (!empty($filtros['mes_venta'])) {
            $queryGrupos->whereMonth('fecha_venta', $filtros['mes_venta']);
        }

        // Paso 1: obtener grupos únicos paginados
        $gruposPaginados = $queryGrupos->select('grupo_venta')
            ->selectRaw('MAX(fecha_venta) as ultima_fecha, MAX(id) as max_id')
            ->groupBy('grupo_venta')
            ->orderByDesc('ultima_fecha') // Orden principal: fecha de venta más reciente
            ->orderByDesc('max_id')       // Orden secundario: ID más alto
            ->paginate($perPage);

        // Paso 2: obtener todos los registros de esos grupos
        $ventas = VentaCochinilla::whereIn('grupo_venta', $gruposPaginados->pluck('grupo_venta'))
            ->orderBy('grupo_venta')
            ->orderBy('id')
            ->get();

        // Paso 3: procesar agrupamiento

        $ventasAgrupadas = $ventas->groupBy('grupo_venta')->flatMap(function ($grupo) {
            $total = $grupo->sum(fn($venta) => floatval($venta->cantidad_seca));
            $ultimo = $grupo->last();

            // Si alguno no está aprobado, el grupo no está aprobado
            $grupoAprobado = $grupo->every(fn($venta) => $venta->aprobado_admin);

            return $grupo->map(function ($venta) use ($ultimo, $total, $grupoAprobado) {
                $venta->es_ultimo = $venta->id === $ultimo->id;
                $venta->total_venta = $venta->es_ultimo ? $total : null;
                $venta->esta_aprobado = $grupoAprobado;
                return $venta;
            });
        });


        // Paso 4: devolver paginator real
        return new LengthAwarePaginator(
            $ventasAgrupadas,
            $gruposPaginados->total(),
            $gruposPaginados->perPage(),
            $gruposPaginados->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    public static function registrarEntrega(array $datos, ?string $grupoExistente = null, ?string $fechaReferencia = null): array
    {
        $fechaReferencia = $fechaReferencia ?? now();
        $grupo = $grupoExistente ?? FormatoHelper::generarCodigoGrupo($fechaReferencia);

        // Si se está editando, eliminar las anteriores
        if ($grupoExistente) {
            VentaCochinilla::where('grupo_venta', $grupoExistente)->delete();
        }

        $ventas = collect($datos)
            ->filter(fn($venta) => !empty($venta['cliente']) || !empty($venta['item']))
            ->map(function ($venta) use ($grupo) {
                return [
                    'cochinilla_ingreso_id' => $venta['ingreso_id'] ?? null,
                    'grupo_venta' => $grupo,
                    'origen_especial' => false,
                    'fecha_filtrado' => FormatoHelper::parseFecha($venta['fecha_filtrado'] ?? null),
                    'cantidad_seca' => $venta['cantidad_seca'] ?? 0,
                    'condicion' => $venta['condicion'] ?? '',
                    'cliente' => $venta['cliente'] ?? '',
                    'cliente_facturacion' => null,
                    'item' => $venta['item'] ?? '',
                    'fecha_venta' => FormatoHelper::parseFecha($venta['fecha_venta'] ?? null),
                    'campo' => $venta['campo'] ?? null,
                    'procedencia' => $venta['procedencia'] ?? null,
                    'observaciones' => $venta['observaciones'] ?? '',
                    'contabilizado' => false,
                    'aprobado_admin' => false,
                    'aprobado_facturacion' => false,
                    'fecha_aprobacion_admin' => null,
                    'fecha_aprobacion_facturacion' => null,
                    'aprobador_admin' => null,
                    'aprobador_facturacion' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        $insertados = self::cargar($ventas);

        return [
            'grupo' => $grupoExistente ?? $grupo,
            'cantidad' => $insertados,
        ];
    }

    public static function cargar($nuevos): int
    {
        $nuevos = collect($nuevos);
        $totalInsertados = 0;

        $nuevos->chunk(1000)->each(function ($chunk) use (&$totalInsertados) {
            $insertados = DB::table('venta_cochinillas')->insert($chunk->toArray());
            // insert() retorna true/false, así que contamos nosotros
            $totalInsertados += count($chunk);
        });

        return $totalInsertados;
    }

    public static function guardar(array $data, ?int $ventaId = null)
    {
        if ($ventaId) {
            $venta = VentaCochinilla::findOrFail($ventaId);
            $venta->update($data);
            return $venta;
        }

        return VentaCochinilla::create($data);
    }
}