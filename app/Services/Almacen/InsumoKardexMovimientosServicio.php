<?php

namespace App\Services\Almacen;

use App\Exports\KardexProductoExport;
use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\Empresa;
use App\Models\InsKardex;
use App\Models\InsKardexMovimiento;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Servicio encargado de generar el detalle de movimientos del Kardex (InsKardexMovimiento)
 * a partir de las Compras y Salidas de Almacén, aplicando los métodos PEPS o Promedio Ponderado.
 */
class InsumoKardexMovimientosServicio
{
    private const METODO_PEPS = 'PEPS';
    private const METODO_PROMEDIO = 'PROMEDIO_PONDERADO';
    private const EPSILON = 0.000000001; // Margen de tolerancia para números flotantes

    // Variables acumulativas para el stock y costo, manteniendo alta precisión (decimales)
    private float $stockAcumulado = 0.0;
    private float $costoTotalAcumulado = 0.0;
    private array $capasPEPS = []; // Solo para PEPS: [ [ 'stock' => x, 'costo_unitario' => y ], ... ]

    /**
     * Genera los movimientos detallados del Kardex para un InsumoKardex específico.
     *
     * @param InsKardex $insumoKardex El Kardex padre a procesar.
     * @throws Exception
     */
    public function generarMovimientos(InsKardex $insumoKardex): void
    {
        // ... (código inalterado) ...
        $this->inicializarAcumuladores($insumoKardex);
        $movimientosOrdenados = $this->obtenerMovimientosBase($insumoKardex);

        if ($movimientosOrdenados->isEmpty()) {
            throw new Exception("No hay movimientos de Compra ni Salida para generar el Kardex.");
        }

        DB::beginTransaction();
        try {
            InsKardexMovimiento::where('kardex_id', $insumoKardex->id)->delete();
            $this->crearMovimientoSaldoInicial($insumoKardex);

            foreach ($movimientosOrdenados as $movimientoBase) {
                if ($movimientoBase instanceof CompraProducto) {
                    $this->procesarEntrada($insumoKardex, $movimientoBase);
                } elseif ($movimientoBase instanceof AlmacenProductoSalida) {
                    $this->procesarSalida($insumoKardex, $movimientoBase);
                }
            }

            $this->generarReporteKardex($insumoKardex);
            $this->recalcularSaldos($insumoKardex);
            $this->actualizarSaldosFinales($insumoKardex);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al generar el Kardex: " . $e->getMessage());
        }
    }

    // --------------------------------------------------------------------------
    // Lógica de Inicialización y Obtención de Datos
    // --------------------------------------------------------------------------

    /**
     * Inicializa las variables acumulativas con el saldo inicial del Kardex.
     */
    private function generarReporteKardex($insumoKardex)
    {
        $data = $this->obtenerDatosKardex($insumoKardex);

        if (!$data) {
            throw new Exception("Aparentemente no hay datos, recargue la página y vuelva a intentarlo porfavor");
        }

        $filePath = 'kardex/' . date('Y-m') . '/' .
            $insumoKardex->codigo_existencia . '_' . $insumoKardex->tipo . '_' .
            Str::slug($insumoKardex->producto->nombre_completo) .
            '.xlsx';
        Excel::store(new KardexProductoExport($data), $filePath, 'public');

        $insumoKardex->file = $filePath;
        $insumoKardex->save();
    }
    protected function obtenerDatosKardex($insumoKardex)
    {
        $empresa = Empresa::first();
        $listaKardex = $this->construirListaKardex($insumoKardex);
        $tieneTipo = $insumoKardex->producto->tabla5;
        if (!$tieneTipo) {
            throw new Exception("El producto no tiene un tipo, editar el producto.");

        }

        $periodo = Carbon::parse($insumoKardex->anio)->format('Y');
        //dd($listaKardex);
        return [
            /*'kardexId' => $this->kardexId,
            'productoId' => $this->kardexProductoId,*/
            'esCombustible' => $insumoKardex->producto->categoria_codigo == 'combustible',
            'kardexLista' => $listaKardex,
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $empresa->ruc,
                'razon_social' => $empresa->razon_social,
                'establecimiento' => $empresa->establecimiento,
                'codigo_existencia' => $insumoKardex->codigo_existencia,
                'tipo' => $insumoKardex->producto->tabla5->codigo . ' - ' . $insumoKardex->producto->tabla5->descripcion,
                'descripcion' => $insumoKardex->producto->nombre_comercial,
                'codigo_unidad_medida' => $insumoKardex->producto->tabla6->codigo . ' - ' . $insumoKardex->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ],
        ];
    }
    private function construirListaKardex(InsKardex $kardex): array
    {
        $lista = [];

        // 2️⃣ Obtener todos los movimientos generados
        $movimientos = InsKardexMovimiento::where('kardex_id', $kardex->id)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        foreach ($movimientos as $mov) {

            $lista[] = [
                'tipo' => $mov->tipo_mov, // entrada | salida
                'fecha' => $mov->fecha,
                'tabla10' => $mov->tipo_documento ?? '',
                'serie' => $mov->serie ?? '',
                'numero' => $mov->numero ?? '',
                'tipo_operacion' => $mov->tipo_operacion,

                'entrada_cantidad' => $mov->entrada_cantidad,
                'entrada_costo_unitario' => $mov->entrada_costo_unitario,
                'entrada_costo_total' => $mov->entrada_costo_total,

                'salida_cantidad' => $mov->salida_cantidad,
                'salida_lote' => $mov->salida_lote,
                'salida_maquinaria' => '', // si aplica
                'salida_costo_unitario' => $mov->salida_costo_unitario,
                'salida_costo_total' => $mov->salida_costo_total,

                'saldofinal_cantidad' => $mov->entrada_cantidad ?? $mov->salida_cantidad,
                'saldofinal_costo_unitario' => $mov->entrada_costo_unitario ?? $mov->salida_costo_unitario,
                'saldofinal_costo_total' => $mov->entrada_costo_total ?? $mov->salida_costo_total,
            ];
        }

        return $lista;
    }
    private function inicializarAcumuladores(InsKardex $insumoKardex): void
    {
        $this->stockAcumulado = (float) $insumoKardex->stock_inicial;
        $this->costoTotalAcumulado = (float) $insumoKardex->costo_total;
        $costoUnitarioInicial = $this->stockAcumulado > 0 ? $this->costoTotalAcumulado / $this->stockAcumulado : 0.0;

        $this->capasPEPS = [];
        if ($this->stockAcumulado > 0 && $costoUnitarioInicial > 0) {
            // La capa inicial para PEPS es el saldo inicial
            $this->capasPEPS[] = [
                'stock' => $this->stockAcumulado,
                'costo_unitario' => $costoUnitarioInicial,
            ];
        }
    }

    /**
     * Obtiene y mezcla todas las Compras y Salidas, ordenadas por fecha y ID/índice.
     *
     * @return Collection
     */
    private function obtenerMovimientosBase(InsKardex $insumoKardex): Collection
    {
        $tipoKardex = $insumoKardex->tipo;
        $productoId = $insumoKardex->producto_id;
        $anio = (int) $insumoKardex->anio;

        $fechaInicio = "{$anio}-01-01";
        $fechaFin = "{$anio}-12-31";

        // Compras (Entradas)
        $compras = CompraProducto::where('producto_id', $productoId)
            ->where('tipo_kardex', $tipoKardex)
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_compra')
            ->orderBy('id')
            ->get();

        // Salidas (Almacén)
        $salidas = AlmacenProductoSalida::where('producto_id', $productoId)
            ->where('tipo_kardex', $tipoKardex)
            ->whereBetween('fecha_reporte', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_reporte')
            ->orderBy('indice') // Usa el índice para el orden dentro del mismo día
            ->orderBy('id')
            ->get();

        // Mezclar y ordenar la colección final
        return $compras->concat($salidas)
            ->sortBy(function ($mov) {
                $fecha = $mov instanceof CompraProducto ? $mov->fecha_compra : $mov->fecha_reporte;
                $ordenSecundario = $mov instanceof CompraProducto ? $mov->id : $mov->indice * 1000 + $mov->id; // Mayor peso al índice de salida
                return $fecha . '-' . str_pad($ordenSecundario, 10, '0', STR_PAD_LEFT);
            });
    }

    // --------------------------------------------------------------------------
    // Lógica de Creación de Movimientos
    // --------------------------------------------------------------------------

    /**
     * Crea el primer movimiento de Saldo Inicial.
     */
    private function crearMovimientoSaldoInicial(InsKardex $insumoKardex): void
    {
        if ($insumoKardex->stock_inicial > 0) {
            InsKardexMovimiento::create([
                'kardex_id' => $insumoKardex->id,
                'fecha' => Carbon::createFromFormat('Y', $insumoKardex->anio)->startOfYear()->format('Y-m-d'),
                'tipo_mov' => 'entrada',
                'tipo_documento' => '16', // Saldo Inicial, Código SUNAT Tabla 12
                'tipo_operacion' => 16,
                'entrada_cantidad' => $this->stockAcumulado,
                'entrada_costo_unitario' => round($this->costoTotalAcumulado / $this->stockAcumulado, 13),
                'entrada_costo_total' => $this->costoTotalAcumulado
            ]);
        }
    }

    /**
     * Procesa una CompraProducto (Entrada) y actualiza los acumuladores.
     */
    private function procesarEntrada(InsKardex $kardex, CompraProducto $compra): void
    {
        $cantidad = (float) $compra->stock;
        $costoTotal = (float) $compra->total;
        $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0.0;

        if ($cantidad <= 0)
            return;

        // 1. Actualizar acumuladores (alta precisión)
        $this->stockAcumulado += $cantidad;
        $this->costoTotalAcumulado += $costoTotal;

        // 2. Actualizar capas PEPS (si aplica)
        if ($kardex->metodo_valuacion === self::METODO_PEPS) {
            $this->capasPEPS[] = [
                'stock' => $cantidad,
                'costo_unitario' => $costoUnitario,
            ];
        }

        // 3. Crear Movimiento Kardex (redondeando para la base de datos)
        InsKardexMovimiento::create([
            'kardex_id' => $kardex->id,
            'fecha' => $compra->fecha_compra,
            'tipo_mov' => 'entrada',
            'tipo_documento' => $compra->tipo_compra_codigo,
            'serie' => $compra->serie,
            'numero' => $compra->numero,
            'tipo_operacion' => $compra->tabla12_tipo_operacion,
            'entrada_cantidad' => round($cantidad, 3),
            'entrada_costo_unitario' => round($costoUnitario, 13),
            'entrada_costo_total' => round($costoTotal, 13)
        ]);
    }

    /**
     * Procesa una AlmacenProductoSalida (Salida) y actualiza los acumuladores.
     *
     * @param InsKardex $kardex
     * @param AlmacenProductoSalida $salida
     * @throws Exception
     */
    private function procesarSalida(InsKardex $kardex, AlmacenProductoSalida $salida): void
    {
        $cantidadSalida = (float) $salida->cantidad;
        if ($cantidadSalida <= 0)
            return;

        // --- INICIO: CAMBIOS POR SOLICITUD ---

        // 1. Aplicar tolerancia (epsilon) para evitar errores de redondeo en la comparación de stock
        // Si la salida es mayor que el stock actual, pero por un margen ínfimo, la forzamos a ser igual al stock
        if (($cantidadSalida > $this->stockAcumulado) && abs($cantidadSalida - $this->stockAcumulado) < self::EPSILON) {
            $cantidadSalida = $this->stockAcumulado;
        }

        // 2. SE ELIMINA LA EXCEPCIÓN: La salida se procesa aunque el stock sea insuficiente
        // if ($cantidadSalida > $this->stockAcumulado) {
        //     throw new Exception("Stock insuficiente...");
        // }

        // --- FIN: CAMBIOS POR SOLICITUD ---

        $costoUnitarioSalida = 0.0;
        $costoTotalSalida = 0.0;

        if ($kardex->metodo_valuacion === self::METODO_PEPS) {
            // Lógica PEPS: Consumir de las capas más antiguas
            // Nota: Si el stock es insuficiente, la lógica PEPS lo detectará y el resultado será un costo total de salida
            // menor al esperado (calculado solo con el stock disponible), pero la cantidadSalida será la original.
            [$costoUnitarioSalida, $costoTotalSalida] = $this->calcularCostoSalidaPEPS($cantidadSalida);
        } else {
            // Lógica Promedio Ponderado: Usar el costo unitario actual
            $costoUnitarioSalida = $this->calcularCostoUnitarioPromedio();
            $costoTotalSalida = $cantidadSalida * $costoUnitarioSalida;
        }

        // 1. Actualizar acumuladores (alta precisión)
        $this->stockAcumulado -= $cantidadSalida;
        $this->costoTotalAcumulado -= $costoTotalSalida; // Esto puede llevar a saldos negativos si la salida es mayor

        // 2. Ajuste por errores de coma flotante si el stock queda muy cerca de cero
        if (abs($this->stockAcumulado) < self::EPSILON) {
            $this->stockAcumulado = 0.0;
            $this->costoTotalAcumulado = 0.0;
        }

        // 3. Crear Movimiento Kardex (redondeando para la base de datos)
        InsKardexMovimiento::create([
            'kardex_id' => $kardex->id,
            'fecha' => $salida->fecha_reporte,
            'tipo_mov' => 'salida',
            'tipo_operacion' => 10, // Generalmente para Salida a Producción
            'salida_cantidad' => round($cantidadSalida, 3),
            'salida_costo_unitario' => round($costoUnitarioSalida, 13),
            'salida_costo_total' => round($costoTotalSalida, 13),
            'salida_lote' => $salida->campo_nombre
        ]);

        // Opcional: Actualizar el costo en la tabla de Salidas de Almacén
        $salida->update([
            'costo_por_kg' => round($costoUnitarioSalida, 13),
            'total_costo' => round($costoTotalSalida, 13),
        ]);
    }

    private function recalcularSaldos(InsKardex $kardex): void
    {
        $stock = 0.0;
        $costoTotal = 0.0;

        $movimientos = InsKardexMovimiento::where('kardex_id', $kardex->id)
            ->orderBy('fecha')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($movimientos as $mov) {

            if ($mov->tipo_mov === 'entrada') {

                $stock += (float) $mov->entrada_cantidad;
                $costoTotal += (float) $mov->entrada_costo_total;

            } elseif ($mov->tipo_mov === 'salida') {

                $stock -= (float) $mov->salida_cantidad;
                $costoTotal -= (float) $mov->salida_costo_total;

            }

            // Costo unitario promedio del saldo actual
            $costoUnitario = $stock > 0 ? $costoTotal / $stock : 0;

            $mov->update([
                'saldo_cantidad' => round($stock, 3),
                'saldo_costo_unitario' => round($costoUnitario, 13),
                'saldo_costo_total' => round($costoTotal, 13),
            ]);
        }
    }


    // --------------------------------------------------------------------------
    // Lógica de Valuación (Cálculos de Costo)
    // --------------------------------------------------------------------------

    /**
     * Calcula el costo unitario promedio ponderado actual.
     *
     * @return float
     */
    private function calcularCostoUnitarioPromedio(): float
    {
        if ($this->stockAcumulado > self::EPSILON) {
            return $this->costoTotalAcumulado / $this->stockAcumulado;
        }
        return 0.0;
    }

    /**
     * Calcula el costo de la salida aplicando el método PEPS (FIFO).
     *
     * @param float $cantidadSalida
     * @return array [costoUnitarioPromedioDeSalida, costoTotalSalida]
     * @throws Exception
     */
    private function calcularCostoSalidaPEPS(float $cantidadSalida): array
    {
        $cantidadPendiente = $cantidadSalida;
        $costoTotalSalida = 0.0;

        // ... (código inalterado para el consumo de capas) ...
        $this->capasPEPS = array_filter($this->capasPEPS, fn($capa) => $capa['stock'] > self::EPSILON); // Ajuste con epsilon

        foreach ($this->capasPEPS as $indice => &$capa) {
            if ($cantidadPendiente <= 0)
                break;

            $stockCapa = $capa['stock'];
            $costoUnitarioCapa = $capa['costo_unitario'];

            if ($stockCapa >= $cantidadPendiente) {
                // La capa actual cubre toda la salida restante
                $consumo = $cantidadPendiente;
                $capa['stock'] -= $consumo;
                $costoTotalSalida += $consumo * $costoUnitarioCapa;
                $cantidadPendiente = 0;
            } else {
                // Consumir toda la capa actual (se consumirá todo, aunque sea menor a lo solicitado)
                $consumo = $stockCapa;
                $costoTotalSalida += $consumo * $costoUnitarioCapa;
                $cantidadPendiente -= $consumo;
                $capa['stock'] = 0;
            }
        }

        // Nota: Si $cantidadPendiente > 0, significa que no había suficiente stock en las capas PEPS,
        // pero se calculará el costo total solo por el stock que sí existía.

        // Limpiar capas con stock cero
        $this->capasPEPS = array_filter($this->capasPEPS, fn($capa) => $capa['stock'] > self::EPSILON);

        // Calcular el costo unitario promedio de la salida (si se usaron múltiples capas)
        // Si $costoTotalSalida es 0 o $cantidadSalida es 0, el resultado es 0.
        $costoUnitarioSalida = $cantidadSalida > 0 ? $costoTotalSalida / $cantidadSalida : 0.0;

        return [$costoUnitarioSalida, $costoTotalSalida];
    }
    private function actualizarSaldosFinales(InsKardex $insumoKardex): void
    {
        $insumoKardex->update([
            'stock_final' => round($this->stockAcumulado, 3),
            'costo_final' => round($this->costoTotalAcumulado, 13),
        ]);
    }
}