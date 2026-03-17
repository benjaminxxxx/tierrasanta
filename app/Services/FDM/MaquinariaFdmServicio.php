<?php

namespace App\Services\FDM;

use App\Models\AlmacenProductoSalida;
use App\Models\ParametroMensual;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaquinariaFdmServicio
{
    public $mes;
    public $anio;
    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }
    public function calcularCosto()
    {
        $this->verificarCostoMaquinariaPaso1();
        $this->verificarCostoMaquinariaPaso2();
        $this->verificarCostoMaquinariaPaso3();

        return $this->calcularYGuardarCostoFinal();
    }
    public function calcularYGuardarCostoFinal(): array
    {
        // Salidas de combustible del mes con sus distribuciones FDM y movimiento kardex
        $salidas = AlmacenProductoSalida::with([
            'distribuciones',
            'maquinaria',
            'kardexMovimiento.kardex', // para saber tipo blanco/negro
        ])
            ->whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereNotNull('maquinaria_id')
            ->whereNotNull('movimiento_id')
            ->get();

        $filasBlancoFDM = [];
        $filasNegroFDM = [];
        $totalBlanco = 0;
        $totalNegro = 0;

        foreach ($salidas as $salida) {
            $tipo = $salida->kardexMovimiento?->kardex?->tipo; // 'blanco' | 'negro'
            if (!in_array($tipo, ['blanco', 'negro']))
                continue;

            // Total horas de TODAS las distribuciones de esta salida
            $todasDistribuciones = $salida->distribuciones;
            $totalHorasSalida = $todasDistribuciones->sum(fn($d) => $d->horas);

            // Solo distribuciones FDM
            $distribucionesFDM = $todasDistribuciones->filter(
                fn($d) => strtoupper(trim($d->campo)) === 'FDM'
            );

            foreach ($distribucionesFDM as $dist) {
                $horasFDM = $dist->horas;
                $ratio = $totalHorasSalida > 0 ? $horasFDM / $totalHorasSalida : 0;
                $cantFDM = round($salida->cantidad * $ratio, 4);
                $precioUnitario = $salida->costo_por_kg ?? 0;
                $costoFDM = round($cantFDM * $precioUnitario, 4);

                $fila = [
                    'fecha' => $dist->fecha,
                    'maquinaria' => $dist->maquinaria?->nombre,
                    'actividad' => $dist->actividad,
                    'campo' => $dist->campo,
                    'hora_inicio' => $dist->hora_inicio,
                    'hora_salida' => $dist->hora_salida,
                    'horas_fdm' => $horasFDM,
                    'total_horas_salida' => $totalHorasSalida,
                    'ratio' => round($ratio, 4),
                    'cantidad_salida' => $salida->cantidad,
                    'cantidad_fdm' => $cantFDM,
                    'precio_unitario' => $precioUnitario,
                    'costo_fdm' => $costoFDM,
                ];

                if ($tipo === 'blanco') {
                    $filasBlancoFDM[] = $fila;
                    $totalBlanco += $costoFDM;
                } else {
                    $filasNegroFDM[] = $fila;
                    $totalNegro += $costoFDM;
                }
            }
        }

        // Generar excels
        $archivoBlanco = !empty($filasBlancoFDM)
            ? self::generarExcelCombustibleFdm($filasBlancoFDM, $totalBlanco, 'BLANCO', $this->anio, $this->mes)
            : null;

        $archivoNegro = !empty($filasNegroFDM)
            ? self::generarExcelCombustibleFdm($filasNegroFDM, $totalNegro, 'NEGRO', $this->anio, $this->mes)
            : null;

        // Guardar en ParametroMensual
        ParametroMensual::establecerMonto($this->mes, $this->anio, 'combustible_fdm_monto_blanco', round($totalBlanco, 4), $archivoBlanco);
        ParametroMensual::establecerMonto($this->mes, $this->anio, 'combustible_fdm_monto_negro', round($totalNegro, 4), $archivoNegro);

        return [
            'total_blanco' => $totalBlanco,
            'total_negro' => $totalNegro,
            'archivo_blanco' => $archivoBlanco,
            'archivo_negro' => $archivoNegro,
        ];
    }

    private static function generarExcelCombustibleFdm(
        array $filas,
        float $total,
        string $tipo,
        int $anio,
        int $mes
    ): string {
        $hojasRequeridas = ['CONSUMO_MAQUINARIA_FDM'];
        $hojas = ExcelHelper::cargarHojasDesdePlantilla(
            'reporte_combustible_fdm.xlsx',
            $hojasRequeridas
        );

        $sheet = $hojas['CONSUMO_MAQUINARIA_FDM'];

        $fila = $sheet->getHighestDataRow();
        $start = $fila;
        $orden = 1;

        foreach ($filas as $row) {
            $sheet->setCellValue("A{$fila}", $orden);
            $sheet->setCellValue("B{$fila}", Carbon::parse($row['fecha'])->format('d/m/Y'));
            $sheet->setCellValue("C{$fila}", $row['maquinaria']);
            $sheet->setCellValue("D{$fila}", $row['actividad']);
            $sheet->setCellValue("E{$fila}", $row['campo']);
            $horaInicioDecimal = Carbon::parse($row['hora_inicio'])->hour / 24
                + Carbon::parse($row['hora_inicio'])->minute / 1440;

            $horaFinDecimal = Carbon::parse($row['hora_salida'])->hour / 24
                + Carbon::parse($row['hora_salida'])->minute / 1440;

            $sheet->setCellValue("F{$fila}", $horaInicioDecimal);
            $sheet->setCellValue("G{$fila}", $horaFinDecimal);

            // H = Horas FDM → fórmula en vez de valor estático
            $sheet->setCellValue("H{$fila}", "=(G{$fila}-F{$fila})*24");

            // I = Total horas de todas las distribuciones de esta salida (dato puro, no calculable en Excel sin datos hermanos)
            $sheet->setCellValue("I{$fila}", $row['total_horas_salida']);

            // J = Ratio → H / I
            $sheet->setCellValue("J{$fila}", "=IF(I{$fila}>0,H{$fila}/I{$fila},0)");

            // K = Cantidad total de la salida (dato puro desde BD)
            $sheet->setCellValue("K{$fila}", $row['cantidad_salida']);

            // L = Cantidad FDM → K * J
            $sheet->setCellValue("L{$fila}", "=K{$fila}*J{$fila}");

            // M = Precio unitario (dato puro desde kardex)
            $sheet->setCellValue("M{$fila}", $row['precio_unitario']);

            // N = Costo FDM → L * M
            $sheet->setCellValue("N{$fila}", "=L{$fila}*M{$fila}");

            $fila++;
            $orden++;
        }

        // Totales con nombres de columna de la tabla
        $sheet->setCellValue("A{$fila}", 'TOTALES');
        $sheet->setCellValue("H{$fila}", "=SUM(tblConsumoMaquinariaFDM[HORAS FDM])");
        $sheet->setCellValue("I{$fila}", "=SUM(tblConsumoMaquinariaFDM[TOTAL HORAS])");
        $sheet->setCellValue("L{$fila}", "=SUM(tblConsumoMaquinariaFDM[CANTIDAD FDM])");
        $sheet->setCellValue("N{$fila}", "=SUM(tblConsumoMaquinariaFDM[COSTO FDM])");

        $formatoHora = 'HH:MM';
        $sheet->getStyle("F4:F1000")->getNumberFormat()->setFormatCode($formatoHora);
        $sheet->getStyle("G4:G1000")->getNumberFormat()->setFormatCode($formatoHora);


        // Ajustar rango de la tabla
        $sheet->getTableByName('tblConsumoMaquinariaFDM')
            ->setRange("A3:N" . ($fila - 1));

        // Guardar archivo
        $folder = "gastos_combustible_fdm/{$anio}";
        $fileName = "REPORTE_COMBUSTIBLE_FDM_{$tipo}_{$anio}_{$mes}.xlsx";
        $filePath = "{$folder}/{$fileName}";

        Storage::disk('public')->makeDirectory($folder);

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $writer = new Xlsx($sheet->getParent());
        $writer->save(Storage::disk('public')->path($filePath));

        return $filePath;
    }
    /**
     * PASO 1 — Verificación de distribuciones de combustible
     *
     * Objetivo:
     * Validar que todas las salidas de combustible asociadas a maquinaria
     * del mes/año tengan al menos una distribución registrada hacia un campo.
     *
     * Regla de validación:
     * - Se buscan registros en `almacen_producto_salidas` del periodo actual
     *   que tengan `maquinaria_id` y que NO tengan distribuciones asociadas.
     *
     * Resultado:
     * - Si existen registros sin distribución:
     *      Estado: Pendiente
     *      Acción sugerida: Registrar distribuciones de combustible por campo.
     *      Se guarda el flag mensual `combustible_fdm_paso1 = false`
     *      y se lanza una excepción con el detalle.
     *
     * - Si todos los registros tienen distribución:
     *      Estado: Completo
     *      Se guarda el flag mensual `combustible_fdm_paso1 = true`.
     *
     * Este paso asegura que el consumo de combustible esté correctamente
     * asignado antes de continuar con el cálculo de costos de maquinaria FDM.
     */
    public function verificarCostoMaquinariaPaso1()
    {
        $registrosSinDistribuciones = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereNotNull('maquinaria_id')
            ->whereDoesntHave('distribuciones')->count();

        if ($registrosSinDistribuciones > 0) {
            $mensaje = "Existen {$registrosSinDistribuciones} registro(s) de salida sin distribuciones.";
            ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso1', false, $mensaje);
            throw new Exception($mensaje);
        }
        ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso1', true, 'Todas las salidas tienen distribución asignada.');
    }
    public function verificarCostoMaquinariaPaso2()
    {
        $registrosSinDistribuciones = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereNotNull('maquinaria_id')
            ->whereNull('tipo_kardex')->count();

        if ($registrosSinDistribuciones > 0) {
            $mensaje = "Existen {$registrosSinDistribuciones} registro(s) de salida sin asignación de Kardex.";
            ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso2', false, $mensaje);
            throw new Exception($mensaje);
        }
        ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso2', true, 'Todas las salidas tienen kardex asignado.');
    }
    public function verificarCostoMaquinariaPaso3(): void
    {
        $sinMovimiento = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereNotNull('maquinaria_id')
            ->whereNull('movimiento_id')
            ->count();

        if ($sinMovimiento > 0) {
            $mensaje = "Existen {$sinMovimiento} salida(s) sin kardex generado. Regenera el kardex de combustible.";
            ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso3', false, $mensaje);
            throw new Exception($mensaje);
        }

        ParametroMensual::establecerFlag($this->mes, $this->anio, 'combustible_fdm_paso3', true, 'Kardex generado y costos asignados.');
    }
}
