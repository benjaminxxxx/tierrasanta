<?php

namespace App\Services\Almacen;

use App\Models\Almacen;
use App\Models\AlmacenProductoSalida;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\InsKardex;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenService;
use App\Services\AlmacenServicio;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\InformacionGeneral\MaquinariaServicio;
use App\Services\ProductoServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InsumoKardexImportarServicio
{
    // Constantes para mayor legibilidad
    private const INDICE_INICIO_DATOS = 16;
    private const COLUMNA_FECHA = 0; // Columna A
    private const COLUMNA_TIPO_OPERACION = 4; // Columna E (Tabla 12)
    private const COLUMNA_CAMPO_LOTE = 9; // Columna J (Campo/Lote en la cabecera)

    public function previsualizar($archivoExcelKardex, InsKardex $insumoKardex): array
    {
        $ruta = $archivoExcelKardex->getRealPath();
        $codigoExistencia = $insumoKardex->codigo_existencia;

        $this->validarHojaExiste($ruta, $codigoExistencia);

        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$codigoExistencia]);
        $spreadsheet = $reader->load($ruta);
        $hoja = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray();

        $this->validarEstructuraBasica($filas);

        $saldoInicialPropuesto = $this->extraerSaldoInicialPropuesto($hoja, $filas);

        $filtroCampos = [];
        if ($insumoKardex->producto->categoria_codigo === 'combustible') {
            $nombresMaquinaria = collect($filas)->skip(self::INDICE_INICIO_DATOS)
                ->pluck(self::COLUMNA_CAMPO_LOTE)->filter()->toArray();
            MaquinariaServicio::validarMaquinariasDesdeExcel($nombresMaquinaria); // solo valida, no usa el resultado aquí
        } else {
            $filtroCampos = $this->obtenerYValidarCampos($filas);
        }

        $this->validarRangoFechas($hoja, $filas, $insumoKardex);

        [$comprasPropuestas, $salidasPropuestas] = $this->extraerDatosTransacciones($hoja, $filas, $insumoKardex, $filtroCampos);

        // Agrupar entradas por documento (fecha+serie+numero) — es lo que en el Excel
        // representa "un solo comprobante", aunque acá solo tenga la línea de este producto.
        $comprasAgrupadas = $this->agruparComprasPorDocumento($comprasPropuestas);

        return [
            'saldo_inicial' => [
                'actual' => [
                    'stock_inicial' => (float) $insumoKardex->stock_inicial,
                    'costo_total' => (float) $insumoKardex->costo_total,
                ],
                'propuesto' => $saldoInicialPropuesto,
            ],
            'compras' => [
                'actuales' => $this->obtenerComprasActuales($insumoKardex),
                'propuestas' => $comprasAgrupadas,
            ],
            'salidas' => [
                'actuales' => $this->obtenerSalidasActuales($insumoKardex),
                'propuestas' => $salidasPropuestas,
            ],
        ];
    }
    public function confirmarImportacion(array $datosPropuestos, InsKardex $insumoKardex): array
    {
        $compraService = app(CompraService::class);

        return DB::transaction(function () use ($datosPropuestos, $insumoKardex, $compraService) {

            $insumoKardex->movimientos()->delete();

            $this->eliminarSalidasExistentes($insumoKardex);
            $this->eliminarComprasExistentes($insumoKardex);

            if ($datosPropuestos['saldo_inicial']['propuesto']) {
                $insumoKardex->update($datosPropuestos['saldo_inicial']['propuesto']);
            }

            $comprasCreadas = 0;
            $salidasCreadas = 0;

            foreach ($datosPropuestos['compras']['propuestas'] as $grupo) {
                $comprasCreadas += $this->insertarGrupoCompra($grupo, $insumoKardex, $compraService);
            }

            $salidasCreadas = $this->insertarSalidas($datosPropuestos['salidas']['propuestas'], $insumoKardex);

            return compact('comprasCreadas', 'salidasCreadas');
        });
    }
    private function insertarGrupoCompra(array $grupo, InsKardex $insumoKardex, CompraService $compraService): int
    {
        // La búsqueda/creación de cabecera se mantiene manual, a propósito:
        // CompraService::crear() siempre crea una cabecera nueva, y aquí
        // necesitamos "reusar si ya existe una con este documento" (otro
        // producto del mismo comprobante pudo haberla creado primero).
        $compra = Compra::where('tipo_kardex', $insumoKardex->tipo)
            ->whereDate('fecha_emision', $grupo['fecha_compra'])
            ->where('serie', $grupo['serie'])
            ->where('numero', $grupo['numero'])
            ->first();

        if (!$compra) {
            $compra = Compra::create([
                //'proveedor_id' => $this->proveedorGenericoId(),
                'almacen_id' => Almacen::first()->id,
                'moneda' => 'PEN',
                'tipo_cambio' => 1.0000,
                'tipo_comprobante_codigo' => $grupo['tipo_compra_codigo'],
                'serie' => $grupo['serie'],
                'numero' => $grupo['numero'],
                'fecha_emision' => $grupo['fecha_compra'],
                'forma_pago' => 'contado',
                'tipo_kardex' => $insumoKardex->tipo,
                'subtotal_neto' => 0,
                'igv_total' => 0,
                'total' => 0,
            ]);
        }

        $creadas = 0;
        foreach ($grupo['lineas'] as $linea) {

            $cantidad = (float) $linea['stock'];
            $total = (float) $linea['total'];
            $costoUnitario = $cantidad > 0 ? $total / $cantidad : 0;
            //dd($linea,$costoUnitario);
            // Misma lógica de costeo y de registro de stock que el módulo
            // principal de Compras — sin duplicar código.
            $compraService->agregarDetalle($compra, [
                'producto_id' => $linea['producto_id'],
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'porcentaje_descuento' => 0,
                'porcentaje_igv' => 18,
            ]);

            $creadas++;
        }

        $compraService->recalcularTotales($compra);

        return $creadas;
    }


    private function eliminarComprasExistentes(InsKardex $insumoKardex): void
    {
        $stockService = app(StockService::class);
        $compraService = app(CompraService::class);
        $detalles = $this->obtenerComprasActuales($insumoKardex);
        $headersAfectados = [];

        foreach ($detalles as $detalle) {
            $stockService->revertirMovimientosDeOrigen(CompraDetalle::class, $detalle->id);
            $headersAfectados[$detalle->compra_id] = true;
            $detalle->delete();
        }

        foreach (array_keys($headersAfectados) as $compraId) {
            $compra = Compra::withTrashed()->find($compraId);

            if (!$compra) {
                continue;
            }

            if ($compra->detalles()->exists()) {
                // Sobrevive con líneas de otros productos: sus totales deben
                // reflejar solo lo que queda, no lo que tenía antes del borrado.
                $compraService->recalcularTotales($compra);
            } else {
                $compra->forceDelete();
            }
        }
    }
    private function insertarSalidas(array $salidas, InsKardex $insumoKardex): int
    {
        $tipo = $insumoKardex->producto->categoria_codigo === 'combustible' ? 'combustible' : 'productos';
        $almacen = AlmacenService::obtenerAlmacenPrincipal();
        app(AlmacenServicio::class)->guardarSalidaMasiva($salidas, $tipo, $almacen->id);
        return count($salidas);
    }
    private function eliminarSalidasExistentes(InsKardex $insumoKardex): void
    {
        $stockService = app(StockService::class);
        $salidas = $this->obtenerSalidasActuales($insumoKardex);

        foreach ($salidas as $salida) {
            // Revierte el MovimientoStock asociado; si el kárdex de ese periodo
            // ya está "cerrado" en el sentido que discutimos, esto lanzará
            // RuntimeException y aborta toda la transacción — comportamiento correcto,
            // no se puede reemplazar data que ya fue usada para cerrar un kárdex.
            $stockService->revertirMovimientosDeOrigen(AlmacenProductoSalida::class, $salida->id);
            $salida->delete();
        }
    }

    private function extraerSaldoInicialPropuesto($hoja, array $filas): ?array
    {
        if (!$this->comprobarSaldoInicial($filas)) {
            return null;
        }

        return [
            'stock_inicial' => (float) $hoja->getCell('F17')->getCalculatedValue(),
            'costo_unitario' => (float) $hoja->getCell('G17')->getCalculatedValue(),
            'costo_total' => (float) $hoja->getCell('H17')->getCalculatedValue(),
        ];
    }

    private function agruparComprasPorDocumento(array $compras): array
    {
        $grupos = [];
        foreach ($compras as $compra) {
            $clave = $compra['fecha_compra'] . '|' . $compra['serie'] . '|' . $compra['numero'];
            $grupos[$clave]['fecha_compra'] ??= $compra['fecha_compra'];
            $grupos[$clave]['serie'] ??= $compra['serie'];
            $grupos[$clave]['numero'] ??= $compra['numero'];
            $grupos[$clave]['tipo_compra_codigo'] ??= $compra['tipo_compra_codigo'];
            $grupos[$clave]['lineas'][] = $compra;
        }
        return array_values($grupos);
    }

    private function obtenerComprasActuales(InsKardex $insumoKardex): Collection
    {
        [$fechaInicio, $fechaFin] = $this->rangoAnio($insumoKardex);

        return CompraDetalle::whereHas('compra', function ($q) use ($insumoKardex, $fechaInicio, $fechaFin) {
            $q->withTrashed() // Incluye compras eliminadas en la condición
                ->where('tipo_kardex', $insumoKardex->tipo)
                ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
        })
            ->where('producto_id', $insumoKardex->producto_id)
            ->with([
                'compra' => function ($q) {
                    $q->withTrashed(); // Carga el modelo de la compra aunque esté eliminado
                }
            ])
            ->get();
    }

    private function obtenerSalidasActuales(InsKardex $insumoKardex): Collection
    {
        [$fechaInicio, $fechaFin] = $this->rangoAnio($insumoKardex);

        return AlmacenProductoSalida::where('producto_id', $insumoKardex->producto_id)
            ->where('tipo_kardex', $insumoKardex->tipo)
            ->whereBetween('fecha_reporte', [$fechaInicio, $fechaFin])
            ->get();
    }

    private function rangoAnio(InsKardex $insumoKardex): array
    {
        $anio = (int) $insumoKardex->anio;
        return [
            Carbon::create($anio, 1, 1)->startOfDay(),
            Carbon::create($anio, 12, 31)->endOfDay(),
        ];
    }
    private function validarHojaExiste(string $ruta, string $codigoExistencia): void
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $hojas = $reader->listWorksheetNames($ruta);

        if (!in_array($codigoExistencia, $hojas)) {
            throw new Exception("No se encontró la hoja con el nombre: **$codigoExistencia**");
        }
    }
    private function validarEstructuraBasica(array $filas): void
    {
        $indiceInicio = self::INDICE_INICIO_DATOS;
        $colFecha = self::COLUMNA_FECHA;
        $colTipoOperacion = self::COLUMNA_TIPO_OPERACION;

        if (!isset($filas[$indiceInicio])) {
            throw new Exception("El archivo no tiene el formato correcto, la información debe iniciar en la fila: " . ($indiceInicio + 1));
        }
        if (!isset($filas[$indiceInicio][$colFecha])) {
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($colFecha + 1) . " para fechas");
        }
        if (!isset($filas[$indiceInicio][$colTipoOperacion])) {
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($colTipoOperacion + 1));
        }
    }
    /**
     * Extrae los nombres de campo/lote del Excel y los valida con el servicio correspondiente.
     *
     * @param array $filas
     * @return array
     * @throws Exception
     */
    private function obtenerYValidarCampos(array $filas): array
    {
        $nombresCampoExcel = collect($filas)
            ->skip(self::INDICE_INICIO_DATOS)
            ->pluck(self::COLUMNA_CAMPO_LOTE)
            ->filter()
            ->toArray();

        $resultadoValidacion = CampoServicio::validarCamposDesdeExcel($nombresCampoExcel);

        if (!empty($resultadoValidacion['invalidos'])) {
            throw new Exception("Los siguientes campos no existen en la base de datos: " . implode(', ', $resultadoValidacion['invalidos']));
        }

        return $resultadoValidacion['filtro'];
    }

    /**
     * Valida que todas las fechas en las filas de datos estén dentro del rango del año del Kardex.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @param array $filas
     * @param InsKardex $insumoKardex
     * @throws Exception
     */
    private function validarRangoFechas($hoja, array $filas, InsKardex $insumoKardex): void
    {
        $anio = (int) $insumoKardex->anio;
        $fechaMinima = Carbon::create($anio, 1, 1)->startOfDay();
        $fechaMaxima = Carbon::create($anio, 12, 31)->endOfDay();
        $indiceInicio = self::INDICE_INICIO_DATOS;

        for ($i = $indiceInicio; $i < count($filas); $i++) {
            $numFilaExcel = $i + 1;
            $valorCeldaFecha = $hoja->getCell('A' . $numFilaExcel)->getValue();

            if ($valorCeldaFecha === '' || $valorCeldaFecha === null) {
                continue;
            }

            $fechaPura = $this->obtenerFechaPuraDesdeCelda($valorCeldaFecha, $numFilaExcel);

            // Validación de rangos usando between
            if (!$fechaPura->between($fechaMinima, $fechaMaxima, true)) {
                throw new Exception(
                    "Error en la fila **{$numFilaExcel}**: La fecha **{$fechaPura->toDateString()}** está fuera del rango permitido "
                    . "({$fechaMinima->toDateString()} - {$fechaMaxima->toDateString()})."
                );
            }
        }
    }
    private function obtenerFechaPuraDesdeCelda($valorCeldaFecha, int $numFilaExcel): Carbon
    {
        $fechaCurrent = null;

        if (is_numeric($valorCeldaFecha)) {
            // Desde número de serie de Excel (como flotante)
            $dateTimeObject = Date::excelToDateTimeObject($valorCeldaFecha);
            $fechaCurrent = Carbon::instance($dateTimeObject);
        } else {
            // Desde string (intentar parsear)
            try {
                $fechaCurrent = Carbon::parse($valorCeldaFecha);
            } catch (Exception $e) {
                throw new Exception("Fecha inválida en la fila **{$numFilaExcel}**: $valorCeldaFecha");
            }
        }

        // Forzar a un string puro 'Y-m-d' y re-parsear para eliminar hora/zona horaria
        $fechaString = $fechaCurrent->format('Y-m-d');
        return Carbon::parse($fechaString)->startOfDay();
    }
    private function extraerDatosTransacciones($hoja, array $filas, InsKardex $insumoKardex, array $filtroCampos): array
    {
        $datosCompra = [];
        $datosSalida = [];
        $indiceInicio = self::INDICE_INICIO_DATOS;
        $tieneSaldoInicial = $this->comprobarSaldoInicial($filas);

        for ($i = $indiceInicio; $i < count($filas); $i++) {
            $numFilaExcel = $i + 1;
            $fila = $filas[$i];

            if ($i == $indiceInicio && $tieneSaldoInicial) {
                continue;
            }

            $tipoOperacion = trim($fila[self::COLUMNA_TIPO_OPERACION]);
            $valorCeldaFecha = $hoja->getCell('A' . $numFilaExcel)->getValue();
            $fechaPura = $this->obtenerFechaPuraDesdeCelda($valorCeldaFecha, $numFilaExcel);

            $datosCompra = array_merge($datosCompra, $this->procesarEntrada($fila, $hoja, $numFilaExcel, $insumoKardex, $fechaPura, $tipoOperacion));
            $datosSalida = array_merge($datosSalida, $this->procesarSalida($hoja, $numFilaExcel, $insumoKardex, $fechaPura, $filtroCampos, $tipoOperacion));
        }

        return [$datosCompra, $datosSalida];
    }
    /**
     * Procesa la entrada (Compra) de una fila de Excel si aplica.
     *
     * @param array $fila
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @param int $numFilaExcel
     * @param InsKardex $insumoKardex
     * @param Carbon $fechaPura
     * @param string $tipoOperacion
     * @return array
     * @throws Exception
     */
    private function procesarEntrada(array $fila, $hoja, int $numFilaExcel, InsKardex $insumoKardex, Carbon $fechaPura, string $tipoOperacion): array
    {
        $datosCompra = [];
        $entradaCantidad = (float) $hoja->getCell('F' . $numFilaExcel)->getCalculatedValue();
        $entradaCostoTotal = (float) $hoja->getCell('H' . $numFilaExcel)->getCalculatedValue();

        if ($entradaCantidad > 0 && $entradaCostoTotal > 0) {
            // Compra (Entrada)
            $tabla10 = trim($fila[1]);
            $serie = trim($fila[2]);
            $numero = trim($fila[3]);

            if (!$tabla10) {
                throw new Exception("Falta el tipo de compra (Tabla 10) en la fila **{$numFilaExcel}**.");
            }
            if ((int) $tipoOperacion !== 2) {
                throw new Exception("Existen valores para una compra, pero el código registrado en la fila **{$numFilaExcel}** no es **2**.");
            }

            $tipoCompraCodigo = isset($tabla10) ? str_pad($tabla10, 2, '0', STR_PAD_LEFT) : null;

            $datosCompra[] = [
                'producto_id' => $insumoKardex->producto_id,
                'fecha_compra' => $fechaPura->format('Y-m-d'),
                'costo_por_kg' => $entradaCostoTotal / $entradaCantidad,
                'total' => $entradaCostoTotal,
                'stock' => $entradaCantidad,
                'tipo_compra_codigo' => $tipoCompraCodigo,
                'serie' => $serie,
                'numero' => $numero,
                'tabla12_tipo_operacion' => $tipoOperacion,
                'tipo_kardex' => $insumoKardex->tipo,
                'estado' => 1
            ];
        }

        return $datosCompra;
    }

    /**
     * Procesa la salida de producción de una fila de Excel si aplica.
     *
     * @param array $fila
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @param int $numFilaExcel
     * @param InsKardex $insumoKardex
     * @param Carbon $fechaPura
     * @param array $filtroCampos
     * @param string $tipoOperacion
     * @return array
     * @throws Exception
     */
    private function procesarSalida($hoja, int $numFilaExcel, InsKardex $insumoKardex, Carbon $fechaPura, array $filtroCampos, string $tipoOperacion): array
    {
        $datosSalida = [];
        $salidaCantidad = (float) $hoja->getCell('I' . $numFilaExcel)->getCalculatedValue();
        $salidaLoteNombre = trim($hoja->getCell('J' . $numFilaExcel)->getValue()); // J es la columna 9 (Columna Lote/Campo)

        if ($salidaCantidad > 0 && $salidaLoteNombre != '') {
            // Salida a Producción
            if ((int) $tipoOperacion !== 10) {
                throw new Exception("Existen valores para una salida a producción, pero el código registrado en la fila **{$numFilaExcel}** no es **10**.");
            }

            // Aplicar el filtro de alias de campo si existe
            $claveLote = mb_strtolower($salidaLoteNombre);
            $nombreCampoFinal = array_key_exists($claveLote, $filtroCampos) ? $filtroCampos[$claveLote] : $salidaLoteNombre;
            $maquinariaId = null;

            if (Producto::esCombustible($insumoKardex->producto_id)) {
                $maquinariaId = $this->obtenerMaquinariaId($nombreCampoFinal);
                $nombreCampoFinal = ''; // Si es combustible, el nombre del campo se limpia.
            }

            $datosSalida[] = [
                'producto_id' => $insumoKardex->producto_id,
                'campo_nombre' => $nombreCampoFinal,
                'cantidad' => $salidaCantidad,
                'fecha_reporte' => $fechaPura->format('Y-m-d'),
                'maquinaria_id' => $maquinariaId,
                'tipo_kardex' => $insumoKardex->tipo,
            ];
        }

        return $datosSalida;
    }

    /**
     * Busca el ID de maquinaria basado en el nombre o alias para productos combustible.
     *
     * @param string $nombreLote
     * @return int|null
     * @throws Exception
     */
    private function obtenerMaquinariaId(string $nombreLote): ?int
    {
        // Usar LOWER() en la base de datos para búsqueda *case-insensitive*
        $nombreLoteBajo = strtolower($nombreLote);

        $maquinaria = Maquinaria::where(DB::raw('LOWER(nombre) COLLATE utf8mb4_general_ci'), $nombreLoteBajo)
            ->orWhere(DB::raw('LOWER(alias_blanco) COLLATE utf8mb4_general_ci'), $nombreLoteBajo)
            ->first();

        if (!$maquinaria) {
            throw new Exception("No existe una Maquinaria con el nombre o alias: **" . $nombreLote . "**");
        }

        return $maquinaria->id;
    }
    private function comprobarSaldoInicial(array $filas): bool
    {
        if (!isset($filas[self::INDICE_INICIO_DATOS])) {
            return false;
        }
        $codigo = (int) $filas[self::INDICE_INICIO_DATOS][self::COLUMNA_TIPO_OPERACION];
        return $codigo === 16;
    }
}