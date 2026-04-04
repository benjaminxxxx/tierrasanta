<?php

namespace App\Services;

use App\Models\AlmacenProductoSalida;
use App\Models\CampoCampania;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\InsKardex;
use App\Models\InsResFertilizanteCampania;
use App\Models\KardexProducto;
use App\Models\PesticidaCampania;
use App\Models\Producto;
use App\Models\ProductoNutriente;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Str;

class AlmacenServicio
{
    private const CAMPOS_IGNORADOS = [
        'creado_por',
        'editado_por',
        'created_at',
        'updated_at',
    ];
    public static function guardarSalidaMasiva(array $filas, string $tipo): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

        // ── PRE-VALIDACIÓN DE STOCK (fuera del transaction) ──────────────
        foreach ($filas as $fila) {
            $id = $fila['id'] ?? null;
            $tipoKardex = $fila['tipo_kardex'] ?? null;
            $productoId = $fila['producto_id'] ?? null;
            $cantidad = (float) ($fila['cantidad'] ?? 0);
            $fechaReporte = $fila['fecha_reporte'] ?? null;

            if (!$tipoKardex || !$productoId || $cantidad <= 0)
                continue;

            $anio = (int) date('Y', strtotime($fechaReporte));
            $kardex = InsKardex::where('producto_id', $productoId)
                ->where('anio', $anio)
                ->where('tipo', $tipoKardex)
                ->first(['id', 'stock_actual']);

            if (!$kardex) {
                throw new Exception(
                    "No existe kardex {$tipoKardex} para el producto ID {$productoId} en {$anio}. "
                    . "Registra una compra primero."
                );
            }

            $stockDisponible = $kardex->stock_actual;

            if ($id) {
                $salidaAnterior = AlmacenProductoSalida::find($id);
                if ($salidaAnterior) {
                    $mismoProducto = (int) $salidaAnterior->producto_id === (int) $productoId;
                    $mismoTipo = $salidaAnterior->tipo_kardex === $tipoKardex;
                    if ($mismoProducto && $mismoTipo) {
                        $stockDisponible += (float) $salidaAnterior->cantidad;
                    }
                }
            }

            if ($stockDisponible < $cantidad) {
                $nombreProducto = Producto::find($productoId)?->nombre_comercial ?? "ID {$productoId}";
                throw new Exception(
                    "Stock {$tipoKardex} insuficiente para \"{$nombreProducto}\". "
                    . "Disponible: {$stockDisponible}, solicitado: {$cantidad}."
                );
            }
        }

        DB::transaction(function () use ($filas, $tipo, &$resultados) {
            $usuarioId = Auth::id();

            foreach ($filas as $fila) {
                $id = $fila['id'] ?? null;
                $esCombustible = $tipo === 'combustible';

                $camposBase = ['fecha_reporte', 'producto_id', 'cantidad'];
                $campoDestino = $esCombustible ? 'maquinaria_id' : 'campo_nombre';

                $camposParaVacioCheck = array_merge($camposBase, [$campoDestino]);
                $filaVacia = collect($camposParaVacioCheck)
                    ->every(fn($campo) => is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '');

                if ($filaVacia) {
                    if ($id) {
                        $salida = AlmacenProductoSalida::findOrFail($id);

                        AuditoriaServicio::registrar(
                            modelo: AlmacenProductoSalida::class,
                            modeloId: $salida->id,
                            accion: 'eliminar',
                            antes: $salida->toArray(),
                            camposIgnorados: self::CAMPOS_IGNORADOS,
                        );

                        $salida->delete();
                        $resultados['eliminados']++;
                    }
                    continue;
                }

                $etiquetas = [
                    'fecha_reporte' => 'Fecha',
                    'producto_id' => 'Producto',
                    'cantidad' => 'Cantidad',
                    'campo_nombre' => 'Campo',
                    'maquinaria_id' => 'Maquinaria',
                ];

                foreach ($camposBase as $campo) {
                    if (is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '') {
                        throw new Exception(
                            "El campo \"{$etiquetas[$campo]}\" es obligatorio."
                            . ($id ? " (ID: {$id})" : '')
                        );
                    }
                }

                if (is_null($fila[$campoDestino] ?? null) || ($fila[$campoDestino] ?? '') === '') {
                    throw new Exception(
                        "El campo \"{$etiquetas[$campoDestino]}\" es obligatorio."
                        . ($id ? " (ID: {$id})" : '')
                    );
                }

                $datos = [
                    'fecha_reporte' => $fila['fecha_reporte'],
                    'producto_id' => $fila['producto_id'],
                    'cantidad' => $fila['cantidad'],
                    'campo_nombre' => $esCombustible ? '' : ($fila['campo_nombre'] ?? ''),
                    'maquinaria_id' => $esCombustible ? ($fila['maquinaria_id'] ?? null) : null,
                    'costo_por_kg' => $fila['costo_por_kg'] ?? null,
                    'total_costo' => $fila['total_costo'] ?? null,
                    'indice' => $fila['indice'] ?? null,
                    'tipo_kardex' => $fila['tipo_kardex'] ?? null,
                ];

                if ($id) {
                    $salida = AlmacenProductoSalida::findOrFail($id);
                    $antes = $salida->toArray();

                    $salida->update(array_merge($datos, ['editado_por' => $usuarioId]));

                    AuditoriaServicio::registrar(
                        modelo: AlmacenProductoSalida::class,
                        modeloId: $salida->id,
                        accion: 'editar',
                        antes: $antes,
                        despues: $salida->fresh()->toArray(),
                        camposIgnorados: self::CAMPOS_IGNORADOS,
                    );

                    $resultados['actualizados']++;
                } else {
                    $salida = AlmacenProductoSalida::create(
                        array_merge($datos, ['creado_por' => $usuarioId])
                    );

                    AuditoriaServicio::registrar(
                        modelo: AlmacenProductoSalida::class,
                        modeloId: $salida->id,
                        accion: 'crear',
                        despues: $salida->toArray(),
                        camposIgnorados: self::CAMPOS_IGNORADOS,
                    );

                    $resultados['creados']++;
                }
            }
        });

        return $resultados;
    }
    /*
        public static function guardarSalidaMasiva(array $filas, string $tipo): array
        {
            $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

            // ── PRE-VALIDACIÓN DE STOCK (fuera del transaction) ──────────────
            foreach ($filas as $fila) {
                $id = $fila['id'] ?? null;
                $tipoKardex = $fila['tipo_kardex'] ?? null;
                $productoId = $fila['producto_id'] ?? null;
                $cantidad = (float) ($fila['cantidad'] ?? 0);
                $fechaReporte = $fila['fecha_reporte'] ?? null;

                // Sin tipo_kardex → sin validación, el trigger dejará null
                if (!$tipoKardex || !$productoId || $cantidad <= 0)
                    continue;

                $anio = (int) date('Y', strtotime($fechaReporte));

                $kardex = InsKardex::where('producto_id', $productoId)
                    ->where('anio', $anio)
                    ->where('tipo', $tipoKardex)
                    ->first(['id', 'stock_actual']);

                if (!$kardex) {
                    throw new Exception(
                        "No existe kardex {$tipoKardex} para el producto ID {$productoId} en {$anio}. "
                        . "Registra una compra primero."
                    );
                }

                // Si es update, devolver la cantidad anterior al stock para la comparación
                $stockDisponible = $kardex->stock_actual;

                if ($id) {
                    $salidaAnterior = AlmacenProductoSalida::find($id);

                    if ($salidaAnterior) {
                        $productoAnterior = (int) $salidaAnterior->producto_id;
                        $tipoAnterior = $salidaAnterior->tipo_kardex;
                        $cantidadAnterior = (float) $salidaAnterior->cantidad;
                        $mismoProducto = $productoAnterior === (int) $productoId;
                        $mismoTipo = $tipoAnterior === $tipoKardex;

                        if ($mismoProducto && $mismoTipo) {
                            // Mismo producto, mismo tipo → el stock disponible incluye lo que ya descontó
                            $stockDisponible += $cantidadAnterior;
                        }
                        // Si cambió producto o tipo → no ajustar, el stock del nuevo producto/tipo
                        // es exactamente lo que hay disponible sin considerar la salida anterior
                    }
                }

                if ($stockDisponible < $cantidad) {
                    $nombreProducto = Producto::find($productoId)?->nombre_comercial ?? "ID {$productoId}";
                    throw new Exception(
                        "Stock {$tipoKardex} insuficiente para \"{$nombreProducto}\". "
                        . "Disponible: {$stockDisponible}, solicitado: {$cantidad}."
                    );
                }
            }

            DB::transaction(function () use ($filas, $tipo, &$resultados) {
                foreach ($filas as $fila) {
                    $id = $fila['id'] ?? null;

                    $esCombustible = $tipo === 'combustible';

                    // Campos obligatorios según tipo
                    $camposBase = ['fecha_reporte', 'producto_id', 'cantidad'];
                    $campoDestino = $esCombustible ? 'maquinaria_id' : 'campo_nombre';

                    // Detectar fila vacía: todos los campos clave son null/vacío
                    $camposParaVacioCheck = array_merge($camposBase, [$campoDestino]);
                    $filaVacia = collect($camposParaVacioCheck)
                        ->every(fn($campo) => is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '');

                    if ($filaVacia) {
                        if ($id) {
                            AlmacenProductoSalida::findOrFail($id)->delete();
                            $resultados['eliminados']++;
                        }
                        continue;
                    }

                    // Validar campos requeridos
                    $etiquetas = [
                        'fecha_reporte' => 'Fecha',
                        'producto_id' => 'Producto',
                        'cantidad' => 'Cantidad',
                        'campo_nombre' => 'Campo',
                        'maquinaria_id' => 'Maquinaria',
                    ];

                    foreach ($camposBase as $campo) {
                        if (is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '') {
                            throw new Exception("El campo \"{$etiquetas[$campo]}\" es obligatorio."
                                . ($id ? " (ID: {$id})" : ''));
                        }
                    }

                    if (is_null($fila[$campoDestino] ?? null) || ($fila[$campoDestino] ?? '') === '') {
                        throw new Exception("El campo \"{$etiquetas[$campoDestino]}\" es obligatorio."
                            . ($id ? " (ID: {$id})" : ''));
                    }

                    // Preparar datos a guardar
                    $datos = [
                        'fecha_reporte' => $fila['fecha_reporte'],
                        'producto_id' => $fila['producto_id'],
                        'cantidad' => $fila['cantidad'],
                        'campo_nombre' => $esCombustible ? '' : ($fila['campo_nombre'] ?? ''),
                        'maquinaria_id' => $esCombustible ? ($fila['maquinaria_id'] ?? null) : null,
                        'costo_por_kg' => $fila['costo_por_kg'] ?? null,
                        'total_costo' => $fila['total_costo'] ?? null,
                        'indice' => $fila['indice'] ?? null,
                        'tipo_kardex' => $fila['tipo_kardex'] ?? null,
                    ];

                    if ($id) {
                        AlmacenProductoSalida::findOrFail($id)->update($datos);
                        $resultados['actualizados']++;
                    } else {
                        AlmacenProductoSalida::create($datos);
                        $resultados['creados']++;
                    }
                }
            });

            return $resultados;
        }*/
    /**
     * Genera un resumen histórico de fertilización por campaña.
     *
     * Esta función se creó con el único propósito de recolectar los datos históricos desde el Kardex
     * (almacén de productos salientes) y volcarlos en la tabla `fertilizacion_campania`.
     * La información generada sirve como resumen general de fertilización por campaña,
     * y permite mantener trazabilidad aunque las tablas relacionadas cambien en el futuro.
     *
     * Si la campaña no tiene un área definida, se lanza una excepción.
     * Se eliminan registros previos antes de insertar los nuevos datos.
     *
     * @param int $campaniaId ID de la campaña.
     * @throws Exception si la campaña no existe o el área no está definida.
     */
    public static function generarFertilizantesXCampania($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            throw new Exception("La campaña no existe");
        }

        if (!$campania->area) {
            throw new Exception("Debe editar la campaña y modificar el área, debe ser diferente de vacío");
        }

        // Eliminar registros anteriores
        InsResFertilizanteCampania::where('campo_campania_id', $campania->id)->delete();

        // Obtener salidas relacionadas al campo
        $query = AlmacenProductoSalida::where('fecha_reporte', '>=', $campania->fecha_inicio);

        if ($campania->fecha_fin) {
            $query->where('fecha_reporte', '<=', $campania->fecha_fin);
        }

        $salidas = $query->where('campo_nombre', $campania->campo)
            ->with('producto')
            ->get();

        $data = [];

        foreach ($salidas as $salida) {

            $producto = $salida->producto;
            if (!$producto) {
                continue;
            }

            $categoria = $producto->categoria_codigo;

            // -------------------------------------------------------
            // 1) CASO ESPECIAL: CORRECTOR DE SALINIDAD
            // -------------------------------------------------------
            if ($categoria === 'corrector_salinidad') {

                $etapa = self::determinarEtapa($campania, $salida->fecha_reporte);

                $data[] = [
                    'campo_campania_id' => $campania->id,
                    'producto_id' => $producto->id,
                    'fecha' => $salida->fecha_reporte,
                    'kg' => null, // aquí no se usa kg
                    'corrector_salinidad_cant' => $salida->cantidad,
                    'etapa' => $etapa,

                    // Nutrientes = null
                    'n_kg' => null,
                    'p_kg' => null,
                    'k_kg' => null,
                    'ca_kg' => null,
                    'mg_kg' => null,
                    'zn_kg' => null,
                    'mn_kg' => null,
                    'fe_kg' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                continue; // Saltar cálculo normal
            }

            // -------------------------------------------------------
            // 2) CASO NORMAL: FERTILIZANTES
            // -------------------------------------------------------
            if ($categoria === 'fertilizante') {

                $nutrientes = ['n', 'p', 'k', 'ca', 'mg', 'zn', 'mn', 'fe'];
                $nutrienteData = [];

                foreach ($nutrientes as $codigo) {
                    $productoNutriente = ProductoNutriente::where('producto_id', $salida->producto_id)
                        ->where('nutriente_codigo', $codigo)
                        ->first();

                    $nutrienteData["{$codigo}_kg"] = $productoNutriente
                        ? ($productoNutriente->porcentaje / 100) * $salida->cantidad
                        : null;
                }

                $etapa = self::determinarEtapa($campania, $salida->fecha_reporte);

                $data[] = array_merge([
                    'campo_campania_id' => $campania->id,
                    'producto_id' => $salida->producto_id,
                    'fecha' => $salida->fecha_reporte,
                    'kg' => $salida->cantidad,
                    'corrector_salinidad_cant' => null,
                    'etapa' => $etapa,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $nutrienteData);

            }
        }

        InsResFertilizanteCampania::insert($data);
    }
    private static function determinarEtapa(CampoCampania $campania, $fecha)
    {
        if ($fecha < $campania->fecha_inicio) {
            throw new Exception("La fecha {$fecha} no puede ser menor a la fecha de inicio de la campaña {$campania->fecha_inicio}", 1);

        }

        if ($campania->infestacion_fecha && $fecha < $campania->infestacion_fecha) {
            return 'infestacion';
        }

        if (
            $campania->reinfestacion_fecha &&
            $fecha >= $campania->infestacion_fecha &&
            $fecha < $campania->reinfestacion_fecha
        ) {
            return 'reinfestacion';
        }

        return 'cosecha';
    }


    public static function generarPesticidasXCampania($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            throw new Exception("La campaña no existe");
        }

        if (!$campania->area) {
            throw new Exception("Debe editar la campaña y modificar el área, debe ser diferente de vacío");
        }

        // Eliminar registros anteriores para evitar duplicados
        PesticidaCampania::where('campo_campania_id', $campania->id)->delete();

        // Obtener salidas de productos relacionadas al campo
        $salidas = AlmacenProductoSalida::where('campo_nombre', $campania->campo)
            ->whereHas('producto', function ($query) {
                $query->where('categoria', 'pesticida');
            })
            ->with('producto')
            ->get();


        $data = [];

        foreach ($salidas as $salida) {
            $kg_ha = ((float) $campania->area > 0) ? $salida->cantidad / (float) $campania->area : 0;

            $data[] = array_merge([
                'campo_campania_id' => $campania->id,
                'producto_id' => $salida->producto_id,
                'fecha' => $salida->fecha_reporte,
                'kg' => $salida->cantidad,
                'kg_ha' => $kg_ha,
            ]);
        }

        PesticidaCampania::insert($data);
    }
    public static function generarResumenFertilizantePorPeriodo($campaniaId)
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $fertilizaciones = InsResFertilizanteCampania::where('campo_campania_id', $campaniaId)
            ->with('producto')
            ->get();

        $resumen = [];

        // Campos que deben sumarse
        $camposSumables = ['kg', 'kg_ha', 'n_ha', 'p_ha', 'k_ha', 'ca_ha', 'mg_ha', 'zn_ha', 'mn_ha', 'fe_ha'];

        $agrupadoPorProducto = $fertilizaciones->groupBy(fn($f) => $f->producto->nombre_comercial ?? 'Producto desconocido');

        foreach ($agrupadoPorProducto as $producto => $registros) {
            $registrosOrdenados = $registros->sortBy('fecha')->values();
            $resumen[$producto] = [];

            $grupoInicio = null;
            $grupoFin = null;
            $grupoAcumulado = [];

            foreach ($registrosOrdenados as $i => $registro) {
                $fecha = Carbon::parse($registro->fecha);

                if (is_null($grupoInicio)) {
                    $grupoInicio = $fecha->copy();
                    $grupoFin = $fecha->copy();
                    $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                } else {
                    $prevFecha = $grupoFin->copy();
                    if ($fecha->isSameDay($prevFecha->addDay())) {
                        $grupoFin = $fecha->copy();
                        $grupoAcumulado = self::acumularValores($grupoAcumulado, $camposSumables, $registro);
                    } else {
                        $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                        $resumen[$producto][$rango] = $grupoAcumulado;

                        // Reiniciar grupo
                        $grupoInicio = $fecha->copy();
                        $grupoFin = $fecha->copy();
                        $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                    }
                }

                if ($i === $registrosOrdenados->count() - 1) {
                    $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                    $resumen[$producto][$rango] = $grupoAcumulado;
                }
            }
        }

        return $resumen;
    }
    public static function generarResumenPesticidaPorPeriodo($campaniaId)
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $pesticidas = PesticidaCampania::where('campo_campania_id', $campaniaId)
            ->with('producto') // Asegúrate que producto trae categoría_pesticida
            ->get();

        $resumen = [];
        $camposSumables = ['kg', 'kg_ha'];

        // Agrupamos primero por categoría
        $agrupadoPorCategoria = $pesticidas->groupBy(function ($f) {
            return $f->producto->categoria_pesticida ?? 'Sin categoría';
        });

        foreach ($agrupadoPorCategoria as $categoria => $grupoPorCategoria) {
            // Luego dentro de cada categoría, agrupamos por nombre del producto
            $agrupadoPorProducto = $grupoPorCategoria->groupBy(fn($f) => $f->producto->nombre_comercial ?? 'Producto desconocido');

            foreach ($agrupadoPorProducto as $producto => $registros) {
                $registrosOrdenados = $registros->sortBy('fecha')->values();
                $resumen[$categoria][$producto] = [];

                $grupoInicio = null;
                $grupoFin = null;
                $grupoAcumulado = [];

                foreach ($registrosOrdenados as $i => $registro) {
                    $fecha = Carbon::parse($registro->fecha);

                    if (is_null($grupoInicio)) {
                        $grupoInicio = $fecha->copy();
                        $grupoFin = $fecha->copy();
                        $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                    } else {
                        $prevFecha = $grupoFin->copy();
                        if ($fecha->isSameDay($prevFecha->addDay())) {
                            $grupoFin = $fecha->copy();
                            $grupoAcumulado = self::acumularValores($grupoAcumulado, $camposSumables, $registro);
                        } else {
                            $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                            $resumen[$categoria][$producto][$rango] = $grupoAcumulado;

                            // Reiniciar grupo
                            $grupoInicio = $fecha->copy();
                            $grupoFin = $fecha->copy();
                            $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                        }
                    }

                    if ($i === $registrosOrdenados->count() - 1) {
                        $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                        $resumen[$categoria][$producto][$rango] = $grupoAcumulado;
                    }
                }
            }
        }

        return $resumen;
    }

    protected static function inicializarAcumulado(array $campos, $registro)
    {
        $acumulado = [];
        foreach ($campos as $campo) {
            $acumulado[$campo] = is_null($registro->$campo) ? null : (float) $registro->$campo;
        }
        return $acumulado;
    }

    protected static function acumularValores(array $acumulado, array $campos, $registro)
    {
        foreach ($campos as $campo) {
            if (!is_null($registro->$campo)) {
                $acumulado[$campo] = isset($acumulado[$campo])
                    ? $acumulado[$campo] + (float) $registro->$campo
                    : (float) $registro->$campo;
            }
        }
        return $acumulado;
    }


    protected static function formatearRango(Carbon $inicio, Carbon $fin, array $meses): string
    {
        if ($inicio->isSameDay($fin)) {
            // Ejemplo: "07 de octubre"
            return $inicio->format('d') . ' de ' . $meses[$inicio->month];
        }

        // Ejemplo: "del 03 al 08 de abril"
        return 'del ' . $inicio->format('d') . ' al ' . $fin->format('d') . ' de ' . $meses[$inicio->month];
    }



    public static function obtenerRegistrosPorFecha($mes, $anio, $tipo, $tipoKardex = null)
    {
        $query = AlmacenProductoSalida::with([
            'distribuciones',
            'maquinaria',
            'producto' => function ($q) {
                $q->withTrashed(); // 🔥 clave
            }
        ]) // Incluir 'producto'
            ->whereMonth('fecha_reporte', $mes)
            ->whereYear('fecha_reporte', $anio);

        // Filtrar por tipo
        if ($tipo === 'combustible') {
            $query->whereHas('producto', function ($q) {
                $q->withTrashed()
                    ->where('categoria_codigo', 'combustible');
            });
        } else {
            $query->whereHas('producto', function ($q) {
                $q->withTrashed()
                    ->where('categoria_codigo', '!=', 'combustible');
            });
        }

        // Filtrar por tipo_kardex si se proporciona
        if (!is_null($tipoKardex)) {
            $query->where('tipo_kardex', $tipoKardex);
        }

        return $query->orderBy('fecha_reporte')         // 1. Ordenar por fecha
            ->orderBy('created_at', 'asc')             // 2. Mantener orden de llegada real
            ->orderByRaw('COALESCE(indice, 0) ASC')    // 3. Manejar null en 'indice'
            ->get();
    }



    public static function resetearStocks(KardexProducto $kardexProducto)
    {
        AlmacenProductoSalida::where('cantidad_kardex_producto_id', $kardexProducto->id)->delete();
        $comprasProcesadas = CompraSalidaStock::where('kardex_producto_id', $kardexProducto->id)->get();
        foreach ($comprasProcesadas as $compra) {
            $compraProducto = CompraProducto::find($compra->compra_producto_id);
            if ($compraProducto) {
                $compraProducto->update([
                    'fecha_termino' => null
                ]);
            }
            $compra->delete();
            //en un futuro usar trigger
        }
        $kardexProducto->salidasStockUsado()->update([
            'cantidad_kardex_producto_id' => null,
            'cantidad_stock_inicial' => null
        ]);
    }
    public static function registrarSalida($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new Exception("No hay información por guardar");
        }

        // Limpiar y estructurar los datos antes de la inserción
        $registros = self::sanearArray($data);

        // Filtrar registros duplicados antes de insertar
        $registrosUnicos = self::filtrarDuplicados($registros);

        if (!empty($registrosUnicos)) {
            AlmacenProductoSalida::insert($registrosUnicos);
            return count($registrosUnicos);
        } else {
            return 0;
        }
    }

    public static function sanearArray($data)
    {
        $columnasPermitidas = [
            'item',
            'producto_id',
            'campo_nombre',
            'cantidad',
            'fecha_reporte',
            'costo_por_kg',
            'total_costo',
            'cantidad_kardex_producto_id',
            'cantidad_stock_inicial',
            'kardex_producto_id',
            'maquinaria_id',
            'indice',
            'tipo_kardex',
        ];

        $registros = [];
        $codigoCarga = Carbon::now()->format('YmdHis');

        foreach ($data as $registro) {
            $limpio = [];

            foreach ($columnasPermitidas as $columna) {
                $limpio[$columna] = $registro[$columna] ?? null;
            }

            if ($limpio['campo_nombre'] == null) {
                $limpio['campo_nombre'] = '';
            }

            $limpio['registro_carga'] = $codigoCarga;

            $registros[] = $limpio;
        }

        return $registros;
    }

    public static function filtrarDuplicados($registros)
    {
        if (empty($registros)) {
            return [];
        }

        // Obtener fecha mínima y máxima del lote
        $fechas = array_column($registros, 'fecha_reporte');
        $fechaMin = min($fechas);
        $fechaMax = max($fechas);

        // Consultar solo los registros en ese rango de fechas
        $existentes = AlmacenProductoSalida::whereBetween('fecha_reporte', [$fechaMin, $fechaMax])->get()->toArray();

        // Crear un mapa de registros existentes con clave única
        $existentesMap = [];
        foreach ($existentes as $existente) {
            $clave = self::generarClaveUnica($existente);
            $existentesMap[$clave] = true;
        }

        // Filtrar los registros que NO existen en la base de datos
        return array_filter($registros, function ($registro) use ($existentesMap) {
            return !isset($existentesMap[self::generarClaveUnica($registro)]);
        });

    }
    private static function generarClaveUnica($registro)
    {
        return $registro['producto_id'] . '-' .
            $registro['campo_nombre'] . '-' .
            self::formatearNumero($registro['cantidad']) . '-' .
            $registro['fecha_reporte'] . '-' .
            ($registro['maquinaria_id'] ?? 'null');
    }

    private static function formatearNumero($valor)
    {
        return number_format((float) $valor, 3, '.', '');
    }
    public static function eliminarRegistroSalida($registroId = null)
    {

        if (!$registroId) {
            throw new Exception('No se ha brindado el Identificador de Registro');
        }

        $registro = AlmacenProductoSalida::find($registroId);

        if (!$registro) {
            throw new Exception('No existe el Registro');
        }

        $registro->delete();
    }
}
