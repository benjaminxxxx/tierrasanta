<?php

namespace App\Services\Almacen;

use App\Models\InsKardex;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\InformacionGeneral\MaquinariaServicio;
use App\Services\ProductoServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InsumoKardexImportarServicio
{
    // Constantes para mayor legibilidad
    private const INDICE_INICIO_DATOS = 16;
    private const COLUMNA_FECHA = 0; // Columna A
    private const COLUMNA_TIPO_OPERACION = 4; // Columna E (Tabla 12)
    private const COLUMNA_CAMPO_LOTE = 9; // Columna J (Campo/Lote en la cabecera)

    /**
     * Procesa el archivo Excel del Kardex para importar las compras y salidas.
     *
     * @param \Illuminate\Http\UploadedFile $archivoExcelKardex
     * @param InsKardex $insumoKardex
     * @return array
     * @throws Exception
     */
    public function procesar($archivoExcelKardex, InsKardex $insumoKardex): array
    {
        $codigoExistencia = $insumoKardex->codigo_existencia;
        $spreadsheet = IOFactory::load($archivoExcelKardex->getRealPath());
        $hoja = $spreadsheet->getSheetByName($codigoExistencia);

        if (!$hoja) {
            throw new Exception("No se encontró la hoja con el nombre: **$codigoExistencia**");
        }

        $filas = $hoja->toArray();

        $this->validarEstructuraBasica($filas);

        $this->procesarSaldoInicial($hoja, $insumoKardex);

        $filtroCampos = [];
        $filtroMaquinarias = [];
        if ($insumoKardex->producto->categoria_codigo === 'combustible') {
            $nombresMaquinaria = collect($filas)
                ->skip(self::INDICE_INICIO_DATOS)
                ->pluck(self::COLUMNA_CAMPO_LOTE)
                ->filter()
                ->toArray();

            $filtroMaquinarias = MaquinariaServicio::validarMaquinariasDesdeExcel($nombresMaquinaria);
            
        } else {
            $filtroCampos = $this->obtenerYValidarCampos($filas);
        }

        $this->validarRangoFechas($hoja, $filas, $insumoKardex);

        [$datosCompra, $datosSalida] = $this->extraerDatosTransacciones($hoja, $filas, $insumoKardex, $filtroCampos);

        $filasAfectadasCompras = ProductoServicio::registrarCompraProducto($datosCompra);
        $filasAfectadasAlmacen = AlmacenServicio::registrarSalida($datosSalida);

        return [
            'filasAfectadasCompras' => $filasAfectadasCompras,
            'filasAfectadasAlmacen' => $filasAfectadasAlmacen,
        ];
    }

    // --- Métodos Privados para la Lógica Específica ---

    /**
     * Realiza las validaciones iniciales de la estructura del archivo.
     *
     * @param array $filas
     * @throws Exception
     */
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
     * Procesa y actualiza la información del Saldo Inicial si existe.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @param InsKardex $insumoKardex
     */
    private function procesarSaldoInicial($hoja, InsKardex $insumoKardex): bool
    {
        $filaInicial = $hoja->toArray()[self::INDICE_INICIO_DATOS];
        $codigoTipoOperacion = (int) $filaInicial[self::COLUMNA_TIPO_OPERACION];

        // El código '16' es para Saldo Inicial
        if ($codigoTipoOperacion !== 16) {
            return false;
        }

        $stockInicial = (float) $hoja->getCell('F17')->getCalculatedValue();
        $costoUnitario = (float) $hoja->getCell('G17')->getCalculatedValue();
        $costoTotal = (float) $hoja->getCell('H17')->getCalculatedValue();

        $insumoKardex->update([
            'stock_inicial' => $stockInicial,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $costoTotal,
        ]);

        return true;
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

    /**
     * Extrae y procesa los datos de Compras (Entradas) y Salidas del Excel.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja
     * @param array $filas
     * @param InsKardex $insumoKardex
     * @param array $filtroCampos
     * @return array [datosCompra, datosSalida]
     * @throws Exception
     */
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
     * Determina si la primera fila de datos es un saldo inicial (código 16).
     *
     * @param array $filas
     * @return bool
     */
    private function comprobarSaldoInicial(array $filas): bool
    {
        if (!isset($filas[self::INDICE_INICIO_DATOS])) {
            return false;
        }
        $codigo = (int) $filas[self::INDICE_INICIO_DATOS][self::COLUMNA_TIPO_OPERACION];
        return $codigo === 16;
    }

    /**
     * Normaliza y obtiene un objeto Carbon sin hora ni zona horaria a partir de un valor de celda.
     *
     * @param mixed $valorCeldaFecha
     * @param int $numFilaExcel
     * @return Carbon
     * @throws Exception
     */
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
}