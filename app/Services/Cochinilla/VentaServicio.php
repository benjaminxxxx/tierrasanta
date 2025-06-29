<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaIngreso;
use App\Models\VentaCochinilla;
use App\Models\VentaCochinillaReporte;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use DB;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class VentaServicio
{
    public static function obtenerInformacionDeEntregaPorGrupo($grupoVenta)
    {


        // 2. Obtener las ventas ya guardadas del grupo
        $ventasGuardadas = VentaCochinilla::where('grupo_venta', $grupoVenta)
            ->get()
            ->keyBy('cochinilla_ingreso_id');

        $fecha = $ventasGuardadas->first()->fecha_venta;

        // 1. Obtener todos los ingresos válidos para venta
        $ingresos = collect(CochinillaServicio::IngresoCochinillaParaVenta($fecha, null))
            ->keyBy('ingreso_id');


        // 3. Combinar
        $resultados = $ingresos->map(function ($ingreso) use ($ventasGuardadas, $fecha) {

            $venta = $ventasGuardadas->get($ingreso['ingreso_id']);

            if ($venta) {
                // Reemplazar con datos ya guardados de la venta
                return [
                    'ingreso_id' => $venta->cochinilla_ingreso_id,
                    'campo' => $venta->campo ?? $ingreso['campo'],
                    'fecha_ingreso' => $ingreso['fecha_ingreso'],
                    'fecha_filtrado' => $venta->fecha_filtrado ?? $ingreso['fecha_filtrado'],
                    'cantidad_fresca' => $ingreso['cantidad_fresca'],
                    'cantidad_seca' => $venta->cantidad_seca,
                    'procedencia' => $ingreso['procedencia'],
                    'venta_cantidad' => $venta->cantidad_seca,
                    'venta_condicion' => $venta->condicion,
                    'venta_cliente' => $venta->cliente,
                    'venta_item' => $venta->item,
                    'venta_fecha' => $fecha,
                    'detalle' => $ingreso['detalle'], // O regenerar si quieres
                    'detalle_stock' => $ingreso['detalle_stock'],
                ];
            }

            // Si no hay venta previa, dejar ingreso base
            return $ingreso;
        })->values()->toArray();

        return [
            'resultados' => $resultados,
            'fecha' => $fecha
        ];
    }
    /*
        public static function obtenerInformacionDeEntregaPorGrupo($registroEntregaGrupoId)
        {
            return VentaCochinilla::where('grupo_venta', $registroEntregaGrupoId)
                ->orderBy('fecha_venta', 'desc')
                ->get()
                ->map(function ($ventaCochinilla) {
                    $ingreso = $ventaCochinilla->ingreso;

                    $campo = $ingreso?->campo ?? $ventaCochinilla->campo;
                    $fechaFiltrado = $ingreso?->fecha_proceso_filtrado ?? $ventaCochinilla->fecha_filtrado;

                    // Generar el detalle de forma más completa
                    $detalle = $ingreso
                        ? "Campo: {$ingreso->campo}\nFecha Ingreso: {$ingreso->fecha}\nFecha Filtrado: {$ingreso->fecha_proceso_filtrado}\nCant. fresca: {$ingreso->total_kilos}"
                        : "Sin Ingreso vinculado\nCampo: {$ventaCochinilla->campo}\nFecha Filtrado: {$fechaFiltrado}";

                    return [
                        'ingreso_id' => $ingreso?->id,
                        'campo' => $campo,
                        'fecha_ingreso' => $ingreso?->fecha,
                        'fecha_filtrado' => $fechaFiltrado,
                        'cantidad_fresca' => $ingreso?->total_kilos,
                        'cantidad_seca' => $ventaCochinilla->cantidad_seca,
                        'procedencia' => $ingreso?->observacion,
                        'venta_cantidad' => $ventaCochinilla->cantidad_seca,
                        'venta_condicion' => $ventaCochinilla->condicion,
                        'venta_cliente' => $ventaCochinilla->cliente,
                        'venta_item' => $ventaCochinilla->item,
                        'venta_fecha' => $ventaCochinilla->fecha_venta,
                        'detalle' => $detalle,
                        'detalle_stock' => $ingreso
                            ? "Cant. Seca: {$ingreso->total_filtrado_primera}\nCant. Vendida: {$ingreso->cantidad_vendida}"
                            : "-",
                    ];
                })
                ->toArray();
        }*/
    public static function datosDeEntrega($mes, $anio)
    {
        return VentaCochinilla::whereYear('fecha_venta', $anio)
            ->whereMonth('fecha_venta', $mes)
            ->with(['ingreso', 'ingreso.infestaciones'])
            ->get()
            ->map(function ($entregaCochinilla) {
                $cochinillaIngreso = $entregaCochinilla->ingreso;
                $fechaIngreso = $cochinillaIngreso?->fecha;
                $total_kilos = $cochinillaIngreso?->total_kilos;
                $fechaFiltrado = $cochinillaIngreso?->fecha_proceso_filtrado;

                $campoOriginalEntrega = $entregaCochinilla->campo;

                // Validar infestaciones
                $camposInfestados = $cochinillaIngreso?->infestaciones->map(function ($infestacion) use ($campoOriginalEntrega) {
                    if ($infestacion->campo_origen_nombre !== $campoOriginalEntrega) {
                        throw new \Exception("El campo de infestación '{$infestacion->campo_origen_nombre}' no coincide con el campo de la entrega '{$campoOriginalEntrega}'.");
                    }
                    return $infestacion->campo_origen_nombre;
                })->unique()->implode(',');

                return [
                    'cochinilla_ingreso_id' => $entregaCochinilla->cochinilla_ingreso_id,
                    'cosecha_fecha_ingreso' => $fechaIngreso,
                    'cosecha_campo' => $campoOriginalEntrega,
                    'cosecha_procedencia' => '',
                    'cosecha_cantidad_fresca' => $total_kilos,

                    'proceso_fecha_filtrado' => $fechaFiltrado,
                    'proceso_cantidad_seca' => $entregaCochinilla->cantidad_seca,
                    'proceso_condicion' => mb_strtolower($entregaCochinilla->condicion),

                    'venta_fecha_venta' => $entregaCochinilla->fecha_venta,
                    'venta_comprador' => $entregaCochinilla->cliente,
                    'venta_infestadores_del_campo' => $camposInfestados
                ];
            });
    }
    public static function vincularIngreso($datos)
    {
        return $datos->map(function ($venta) {
            if (!isset($venta['cosecha_campo']) || !isset($venta['venta_fecha_venta'])) {
                return $venta + ['cosecha_encontrada' => false];
            }

            $fechaVenta = Carbon::parse($venta['venta_fecha_venta']);

            $ingreso = CochinillaIngreso::where('campo', $venta['cosecha_campo'])
                ->whereDate('fecha', '<=', $fechaVenta)
                ->whereDate('fecha', '>=', $fechaVenta->copy()->subDays(60))
                ->with('infestaciones', 'observacionRelacionada')
                ->orderByDesc('fecha')
                ->first();

            if (!$ingreso) {
                return $venta + ['cosecha_encontrada' => false];
            }

            $camposInfestados = $ingreso->campos_infestados;

            return array_merge($venta, [
                'cochinilla_ingreso_id' => $ingreso->id,
                'cosecha_fecha_ingreso' => $ingreso->fecha,
                'cosecha_procedencia' => $ingreso->observacionRelacionada?->descripcion,
                'cosecha_cantidad_fresca' => $ingreso->total_kilos,
                'proceso_fecha_filtrado' => $ingreso->fecha_proceso_filtrado,
                'cosecha_encontrada' => true,
                'venta_infestadores_del_campo' => $camposInfestados,
            ]);
        });
    }
    public static function agruparPorIngreso($datos)
    {
        $collection = collect($datos);

        // Agrupar por llave compuesta
        return $collection
            ->groupBy(function ($item) {
                return implode('|', [
                    $item['cochinilla_ingreso_id'] ?? 'null',
                    $item['cosecha_campo'] ?? '',
                    $item['cosecha_procedencia'] ?? '',
                    $item['proceso_condicion'] ?? '',
                    $item['venta_comprador'] ?? '',
                    $item['venta_fecha_venta'] ?? '',
                ]);
            })
            ->map(function ($grupo) {
                if ($grupo->count() === 1) {
                    // No hay fusión
                    return $grupo->first();
                }

                // Sumar proceso_cantidad_seca
                $sumaSeca = $grupo->sum(function ($item) {
                    return is_numeric($item['proceso_cantidad_seca']) ? $item['proceso_cantidad_seca'] : 0;
                });
                $sumaSeca = $sumaSeca > 0 ? $sumaSeca : null;

                // Concatenar infestadores
                $infestadoresConcat = $grupo
                    ->pluck('venta_infestadores_del_campo')
                    ->filter()
                    ->unique()
                    ->implode(',');

                // Tomar base del primer registro
                $base = $grupo->first();

                return array_merge($base, [
                    'proceso_cantidad_seca' => $sumaSeca,
                    'venta_infestadores_del_campo' => $infestadoresConcat !== '' ? $infestadoresConcat : null,
                    'fusionada' => true,
                ]);
            })
            ->values();
    }
    #region Reporte de Venta
    public static function obtenerReporte($mes, $anio)
    {

        if (is_null($anio)) {
            return collect();
        }

        $query = VentaCochinillaReporte::query();

        if (!is_null($anio)) {
            $query->whereYear('venta_fecha_venta', $anio);
        }

        if (!is_null($mes) && trim($mes) != '') {
            $query->whereMonth('venta_fecha_venta', $mes);
        }

        return $query->get()
            ->map(function ($reporte) {
                return [
                    'cochinilla_ingreso_id' => $reporte->cochinilla_ingreso_id,
                    'cosecha_fecha_ingreso' => Carbon::parse($reporte->cosecha_fecha_ingreso)->format('d/m/Y'),
                    'cosecha_campo' => $reporte->cosecha_campo,
                    'cosecha_procedencia' => $reporte->cosecha_procedencia,
                    'cosecha_cantidad_fresca' => $reporte->cosecha_cantidad_fresca,

                    'proceso_fecha_filtrado' => Carbon::parse($reporte->proceso_fecha_filtrado)->format('d/m/Y'),
                    'proceso_cantidad_seca' => $reporte->proceso_cantidad_seca,
                    'proceso_condicion' => $reporte->proceso_condicion,

                    'venta_fecha_venta' => Carbon::parse($reporte->venta_fecha_venta)->format('d/m/Y'),
                    'venta_comprador' => $reporte->venta_comprador,
                    'venta_infestadores_del_campo' => $reporte->venta_infestadores_del_campo,
                    'fusionada' => $reporte->fusionada,
                    'cosecha_encontrada' => $reporte->cosecha_encontrada,
                ];
            });
    }

    public static function registrarReporteVenta($datos, $mes, $anio)
    {
        $datos = collect($datos);

        // Validar que TODAS las ventas correspondan al mes y año indicado
        $fueraDeRango = $datos->filter(function ($venta) use ($mes, $anio) {
            $fechaNormalizada = FormatoHelper::parseFecha($venta['venta_fecha_venta']);

            if (!$fechaNormalizada) {
                return true; // no se pudo parsear => inválida
            }

            try {
                $fecha = Carbon::parse($fechaNormalizada);
                return $fecha->month != $mes || $fecha->year != $anio;
            } catch (Exception $e) {
                return true;
            }
        });

        if ($fueraDeRango->isNotEmpty()) {
            throw new Exception('Hay ventas con fecha fuera del mes o año seleccionado. Corrige las fechas antes de enviar a contabilidad.');
        }

        // Validar que todas las filas tengan ingreso_id (campo obligatorio para enviar a contabilidad)
        $noVinculadas = $datos->filter(fn($item) => empty($item['cochinilla_ingreso_id']));
        if ($noVinculadas->isNotEmpty()) {
            throw new Exception("No todas las ventas están vinculadas a un ingreso de cochinilla. Revisa y vincula antes de enviar a contabilidad.");
        }

        VentaCochinillaReporte::whereYear('venta_fecha_venta', $anio)->whereMonth('venta_fecha_venta', $mes)->delete();

        // Registrar en la tabla venta_cochinilla_reportes
        $datos->map(function ($venta) {
            return VentaCochinillaReporte::create([
                'cochinilla_ingreso_id' => $venta['cochinilla_ingreso_id'],

                'cosecha_fecha_ingreso' => FormatoHelper::parseFecha($venta['cosecha_fecha_ingreso'] ?? null),
                'cosecha_campo' => $venta['cosecha_campo'] ?? null,
                'cosecha_procedencia' => $venta['cosecha_procedencia'] ?? null,
                'cosecha_cantidad_fresca' => $venta['cosecha_cantidad_fresca'] ?? null,

                'proceso_fecha_filtrado' => FormatoHelper::parseFecha($venta['proceso_fecha_filtrado'] ?? null),
                'proceso_cantidad_seca' => $venta['proceso_cantidad_seca'] ?? null,
                'proceso_condicion' => isset($venta['proceso_condicion'])
                    ? mb_strtolower($venta['proceso_condicion'])
                    : null,

                'venta_fecha_venta' => FormatoHelper::parseFecha($venta['venta_fecha_venta'] ?? null),
                'venta_comprador' => $venta['venta_comprador'] ?? null,
                'venta_infestadores_del_campo' => $venta['venta_infestadores_del_campo'] ?? null,

                'cosecha_encontrada' => $venta['cosecha_encontrada'] ?? false,
                'fusionada' => $venta['fusionada'] ?? false,
            ]);
        });
    }
    #endregion
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

        return [
            'paginado' => new LengthAwarePaginator(
                $ventasAgrupadas,
                $gruposPaginados->total(),
                $gruposPaginados->perPage(),
                $gruposPaginados->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            ),
            'total_venta' => $ventasAgrupadas->sum(fn($v) => $v->total_venta ?? 0),
        ];
    }
    public static function prepararDatosVenta(array $datos, string $grupo, string $fechaReferencia): array
    {
        $errores = [];
        $preparados = [];

        foreach ($datos as $index => $registro) {

            // Campos mínimos de venta
            $venta_cantidad = $registro['venta_cantidad'] ?? null;
            $venta_item = $registro['venta_item'] ?? null;
            $venta_condicion = $registro['venta_condicion'] ?? null;
            $venta_cliente = $registro['venta_cliente'] ?? null;

            // Verifica si es línea vacía (no es venta)
            if (empty($venta_cantidad) && empty($venta_item) && empty($venta_condicion) && empty($venta_cliente)) {
                continue;
            }

            // Validación: Item es obligatorio si hay venta
            $faltantes = [];
            if (empty($venta_item)) {
                $faltantes[] = 'Item';
            }
            if (empty($venta_cantidad)) {
                $faltantes[] = 'Cantidad';
            }
            if (empty($venta_condicion)) {
                $faltantes[] = 'Condición';
            }
            if (empty($venta_cliente)) {
                $faltantes[] = 'Cliente';
            }

            if (!empty($faltantes)) {
                $errores[] = "Registro #" . ($index + 1) . " incompleto. Faltan: " . implode(', ', $faltantes);
                continue;
            }

            // Si pasó validación, se prepara para DB
            $preparados[] = [
                'cochinilla_ingreso_id' => $registro['ingreso_id'] ?? null,
                'grupo_venta' => $grupo,
                'fecha_filtrado' => FormatoHelper::parseFecha($registro['fecha_filtrado'] ?? null),
                'cantidad_seca' => $venta_cantidad,
                'condicion' => $venta_condicion,
                'cliente' => $venta_cliente,
                'item' => $venta_item,
                'fecha_venta' => FormatoHelper::parseFecha($fechaReferencia),
                'campo' => $registro['campo'] ?? null,
                'observaciones' => $registro['observaciones'] ?? null,
                'aprobado_facturacion' => false,
                'fecha_aprobacion_facturacion' => null,
                'aprobador_facturacion' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($errores)) {
            throw new Exception(implode(' | ', $errores));
        }

        if (empty($preparados)) {
            throw new Exception("No hay datos válidos para registrar.");
        }

        return $preparados;
    }

    public static function registrarEntrega(array $datos, ?string $grupoExistente = null, ?string $fechaReferencia = null)
    {
        if (!$fechaReferencia) {
            throw new Exception("La fecha de venta es un campo obligatorio");
        }

        $grupo = $grupoExistente ?? FormatoHelper::generarCodigoGrupo($fechaReferencia);

        // Si se está editando, elimina registros previos
        if ($grupoExistente) {
            self::eliminarRegistroEntrega($grupoExistente);
        }

        // Preparar y validar datos
        $datosPreparados = self::prepararDatosVenta($datos, $grupo, $fechaReferencia);

        // Insertar en DB
        $insertados = self::cargar($datosPreparados);

        return [
            'grupo' => $grupo,
            'cantidad' => $insertados,
        ];
    }


    public static function cargar(array $nuevos): int
    {
        if (empty($nuevos)) {
            return 0;
        }

        DB::beginTransaction();

        try {
            foreach ($nuevos as $registro) {
                VentaCochinilla::create($registro);
            }

            DB::commit();

            return count($nuevos);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new Exception("Error al guardar entregas de venta: " . $e->getMessage(), 0, $e);
        }
    }
    #region Eliminacion
    public static function eliminarRegistroEntrega($grupoVenta)
    {
        VentaCochinilla::where('grupo_venta', $grupoVenta)->delete();
    }
    #endregion
}