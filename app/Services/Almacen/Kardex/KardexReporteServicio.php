<?php

namespace App\Services\Almacen\Kardex;

use App\Models\InsKardex;
use App\Models\InsKardexMovimiento;
use App\Models\InsKardexReporte;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte general de kardex (/gestion_insumos/kardex/reporte/{id}).
 *
 * "Generar resumen" hace todo en un paso: pone al día los kardex del reporte (año, tipo y grupos
 * operativos), recalcula el índice y arma el Excel: hoja INDICE (plantilla rpt_tmpl_kardex.xlsx)
 * + una hoja por producto copiada del Excel de su kardex (F1, F2, P1...).
 * El Excel queda guardado; descargarlo no vuelve a procesar nada.
 */
class KardexReporteServicio
{
    private const PLANTILLA = 'templates/rpt_tmpl_kardex.xlsx';
    private const FILA_INICIO = 4;
    private const FORMATO_IMPORTE = '_-* #,##0.00_-;\-* #,##0.00_-;_-* "-"??_-;_-@_-';
    private const FORMATO_SALDO = '_ * #,##0.000000_ ;_ * \-#,##0.000000_ ;_ * "-"??_ ;_ @_ ';

    public function __construct(
        private KardexActualizacionServicio $actualizacion,
        private InsumoKardexMovimientosServicio $movimientos,
    ) {
    }

    /**
     * Kardex que entran al reporte, ordenados por grupo operativo y código de existencia.
     *
     * @return Collection<InsKardex>
     */
    public function kardexesDelReporte(InsKardexReporte $reporte): Collection
    {
        $grupos = $reporte->grupos_ordenados;

        return InsKardex::with(['producto.categoria', 'producto.tabla6'])
            ->where('tipo', $reporte->tipo_kardex)
            ->where('anio', $reporte->anio)
            ->whereHas('producto.categoria', fn($q) => $q->whereIn('grupo_operativo', $grupos))
            ->get()
            ->sort(function (InsKardex $a, InsKardex $b) use ($grupos) {
                $ga = array_search($a->producto->categoria->grupo_operativo, $grupos, true);
                $gb = array_search($b->producto->categoria->grupo_operativo, $grupos, true);
                return $ga <=> $gb ?: strnatcasecmp((string) $a->codigo_existencia, (string) $b->codigo_existencia);
            })
            ->values();
    }

    /**
     * Pone al día los kardex, recalcula el índice y genera el Excel.
     *
     * @return string[] advertencias (kardex cerrados con cambios posteriores, etc.)
     * @throws Exception si algún kardex no se pudo actualizar (no se genera nada)
     */
    public function generar(InsKardexReporte $reporte): array
    {
        if (empty($reporte->grupos_operativos)) {
            throw new Exception('El reporte no tiene grupos operativos. Edítalo y elige al menos uno.');
        }

        $kardexes = $this->kardexesDelReporte($reporte);
        if ($kardexes->isEmpty()) {
            throw new Exception("No hay kardex {$reporte->tipo_kardex} {$reporte->anio} de "
                . implode(', ', $reporte->grupos_ordenados) . '.');
        }

        [$errores, $advertencias] = $this->asegurarKardexAlDia($kardexes);
        if ($errores) {
            throw new Exception('No se generó el reporte, hay kardex que no se pudieron actualizar: ' . implode(' | ', $errores));
        }

        // Código repetido: la hoja del segundo sale como F1_2, pero seguramente es un error de codificación
        $kardexes->groupBy('codigo_existencia')->filter(fn($g) => $g->count() > 1)
            ->each(function ($g, $codigo) use (&$advertencias) {
                $advertencias[] = "El código {$codigo} está repetido en: "
                    . $g->map(fn($k) => $k->producto->nombre_completo)->join(', ') . '.';
            });

        DB::transaction(function () use ($reporte, $kardexes) {
            $this->guardarDetalles($reporte, $kardexes);

            $reporte->load('detalles');
            $archivoAnterior = $reporte->file;
            $archivo = $this->construirExcel($reporte, $kardexes);

            $reporte->update(['file' => $archivo, 'generado_at' => now()]);

            if ($archivoAnterior && $archivoAnterior !== $archivo) {
                Storage::disk('public')->delete($archivoAnterior);
            }
        });

        return $advertencias;
    }

    /**
     * Motivos por los que el Excel/índice guardado ya no refleja los kardex (vacío = al día).
     *
     * @return string[]
     */
    public function motivosDesactualizado(InsKardexReporte $reporte): array
    {
        if (!$reporte->generado_at) {
            return ['El reporte aún no se ha generado.'];
        }
        if (!$reporte->file || !Storage::disk('public')->exists($reporte->file)) {
            return ['No se encuentra el Excel generado.'];
        }

        $motivos = [];
        $kardexes = $this->kardexesDelReporte($reporte);

        $idsReporte = $reporte->detalles()->pluck('ins_kardex_id')->filter()->sort()->values()->all();
        if ($kardexes->pluck('id')->sort()->values()->all() !== $idsReporte) {
            $motivos[] = 'Se crearon o eliminaron kardex desde la última generación.';
        }

        foreach ($kardexes as $kardex) {
            $etiqueta = "{$kardex->codigo_existencia} {$kardex->producto->nombre_completo}";
            if ($kardex->movimientos_actualizados_at && $kardex->movimientos_actualizados_at->gt($reporte->generado_at)) {
                $motivos[] = "{$etiqueta}: el kardex se regeneró después del reporte.";
            } elseif ($motivo = $this->actualizacion->motivoDesactualizado($kardex)) {
                $motivos[] = "{$etiqueta}: {$motivo}.";
            }
        }

        return $motivos;
    }

    /**
     * Totales por grupo operativo del índice guardado.
     *
     * @return array<string, array{productos:int, entradas_importe:float, salidas_importe:float, saldo_unidades:float, saldo_importe:float}>
     */
    public function totalesPorGrupo(InsKardexReporte $reporte): array
    {
        $totales = [];
        foreach ($reporte->grupos_ordenados as $grupo) {
            $detalles = $reporte->detalles->where('grupo_operativo', $grupo);
            $totales[$grupo] = [
                'productos' => $detalles->count(),
                'entradas_importe' => (float) $detalles->sum('total_entradas_importe'),
                'salidas_importe' => (float) $detalles->sum('total_salidas_importe'),
                'saldo_unidades' => (float) $detalles->sum('saldo_unidades'),
                'saldo_importe' => (float) $detalles->sum('saldo_importe'),
            ];
        }
        return $totales;
    }

    // ------------------------------------------------------------------------------------------

    /**
     * Regenera los kardex desactualizados y el Excel de los que no lo tienen.
     * Un kardex cerrado no se regenera: se usa tal cual y queda como advertencia.
     */
    private function asegurarKardexAlDia(Collection $kardexes): array
    {
        $errores = [];
        $advertencias = [];

        foreach ($kardexes as $kardex) {
            $etiqueta = "{$kardex->codigo_existencia} {$kardex->producto->nombre_completo}";

            try {
                $motivo = $this->actualizacion->motivoDesactualizado($kardex);
                if ($motivo !== null) {
                    if ($kardex->estado === 'cerrado') {
                        $advertencias[] = "{$etiqueta}: está cerrado pero {$motivo}. Se usó tal como está.";
                    } else {
                        $this->movimientos->generarMovimientos($kardex);
                        $kardex->refresh();
                    }
                }

                if (!$kardex->file || !Storage::disk('public')->exists($kardex->file)) {
                    $this->movimientos->generarExcel($kardex);
                }
            } catch (\Throwable $e) {
                $errores[] = "{$etiqueta}: {$e->getMessage()}";
            }
        }

        return [$errores, $advertencias];
    }

    private function guardarDetalles(InsKardexReporte $reporte, Collection $kardexes): void
    {
        $sumas = InsKardexMovimiento::whereIn('kardex_id', $kardexes->pluck('id'))
            ->selectRaw('kardex_id,
                SUM(COALESCE(entrada_cantidad, 0)) as entradas_unidades,
                SUM(COALESCE(entrada_costo_total, 0)) as entradas_importe,
                SUM(COALESCE(salida_cantidad, 0)) as salidas_unidades,
                SUM(COALESCE(salida_costo_total, 0)) as salidas_importe')
            ->groupBy('kardex_id')
            ->get()
            ->keyBy('kardex_id');

        $reporte->detalles()->delete();

        foreach ($kardexes as $kardex) {
            $producto = $kardex->producto;
            $s = $sumas->get($kardex->id);
            $entradasUnidades = (float) ($s->entradas_unidades ?? 0);
            $entradasImporte = (float) ($s->entradas_importe ?? 0);
            $salidasUnidades = (float) ($s->salidas_unidades ?? 0);
            $salidasImporte = (float) ($s->salidas_importe ?? 0);

            $reporte->detalles()->create([
                'ins_kardex_id' => $kardex->id,
                'grupo_operativo' => $producto->categoria->grupo_operativo,
                'codigo_existencia' => $kardex->codigo_existencia,
                'nombre_producto' => $producto->nombre_completo,
                'condicion' => $kardex->estado,
                'unidad_medida' => $producto->tabla6 ? "{$producto->tabla6->codigo} - {$producto->tabla6->alias}" : null,
                'total_entradas_unidades' => $entradasUnidades,
                'total_entradas_importe' => $entradasImporte,
                'total_salidas_unidades' => $salidasUnidades,
                'total_salidas_importe' => $salidasImporte,
                'saldo_unidades' => $entradasUnidades - $salidasUnidades,
                'saldo_importe' => $entradasImporte - $salidasImporte,
            ]);
        }
    }

    private function construirExcel(InsKardexReporte $reporte, Collection $kardexes): string
    {
        $spreadsheet = IOFactory::load(public_path(self::PLANTILLA));
        $indice = $spreadsheet->getSheetByName('INDICE') ?? $spreadsheet->getSheet(0);

        // Nombres de hoja únicos por kardex (normalmente su código: F1, P1...)
        $hojas = [];
        foreach ($kardexes as $kardex) {
            $hojas[$kardex->id] = $this->tituloHojaUnico((string) $kardex->codigo_existencia, $hojas);
        }

        $rangosGrupo = $this->escribirIndice($indice, $reporte, $hojas);
        $this->escribirResumenGrupos($indice, $reporte, $rangosGrupo);

        // Una hoja por producto, copiada del Excel de su kardex
        foreach ($kardexes as $kardex) {
            $externo = IOFactory::load(Storage::disk('public')->path($kardex->file));
            $hoja = $externo->getSheet(0);
            $hoja->setTitle($hojas[$kardex->id], false);
            $spreadsheet->addExternalSheet($hoja);
            // No se desconecta $externo: la hoja copiada comparte sus celdas.
            unset($externo);
        }

        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($indice));

        $carpeta = "kardex/reportes/{$reporte->anio}";
        Storage::disk('public')->makeDirectory($carpeta);
        $archivo = "{$carpeta}/REPORTE_KARDEX_{$reporte->anio}_" . mb_strtoupper($reporte->tipo_kardex)
            . '_' . Str::slug($reporte->nombre) . "_{$reporte->id}.xlsx";

        $writer = new Xlsx($spreadsheet);
        // Excel recalcula al abrir (saldos y totales son fórmulas)
        $writer->setPreCalculateFormulas(false);
        $writer->save(Storage::disk('public')->path($archivo));
        $spreadsheet->disconnectWorksheets();

        return $archivo;
    }

    /**
     * Llena la hoja INDICE desde la fila 4 con el formato de la fila 4 de la plantilla.
     *
     * @return array<string, array{0:int,1:int}> grupo => [fila inicio, fila fin]
     */
    private function escribirIndice(Worksheet $sheet, InsKardexReporte $reporte, array $hojas): array
    {
        $f0 = self::FILA_INICIO;
        $columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        // Formato de la fila modelo, luego se quitan las filas de ejemplo de la plantilla
        $xf = [];
        foreach ($columnas as $col) {
            $xf[$col] = $sheet->getCell("{$col}{$f0}")->getXfIndex();
        }
        $alto = $sheet->getRowDimension($f0)->getRowHeight();
        $ultimaPlantilla = $sheet->getHighestRow();
        if ($ultimaPlantilla >= $f0) {
            $sheet->removeRow($f0, $ultimaPlantilla - $f0 + 1);
        }

        $sheet->setCellValue('A3', 'ÍNDICE DE ' . implode(' Y ', array_map(
            fn($g) => InsKardexReporte::etiquetaGrupo($g),
            $reporte->grupos_ordenados
        )));

        $rangos = [];
        $fila = $f0;
        foreach ($reporte->detalles->sortBy(fn($d) => array_search($d->ins_kardex_id, array_keys($hojas), true)) as $d) {
            foreach ($columnas as $col) {
                $sheet->getCell("{$col}{$fila}")->setXfIndex($xf[$col]);
            }
            $sheet->getRowDimension($fila)->setVisible(true)->setRowHeight($alto);

            $sheet->setCellValue("A{$fila}", $d->codigo_existencia);
            $sheet->setCellValue("B{$fila}", $d->nombre_producto);
            $sheet->setCellValue("C{$fila}", $d->unidad_medida);
            $sheet->setCellValue("D{$fila}", (float) $d->total_entradas_unidades);
            $sheet->setCellValue("E{$fila}", (float) $d->total_entradas_importe);
            $sheet->setCellValue("F{$fila}", (float) $d->total_salidas_unidades);
            $sheet->setCellValue("G{$fila}", (float) $d->total_salidas_importe);
            $sheet->setCellValue("H{$fila}", "=D{$fila}-F{$fila}");
            $sheet->setCellValue("I{$fila}", "=E{$fila}-G{$fila}");

            // Enlace a la hoja del producto
            if (isset($hojas[$d->ins_kardex_id])) {
                $sheet->getCell("B{$fila}")->getHyperlink()->setUrl("sheet://'{$hojas[$d->ins_kardex_id]}'!A1");
                $sheet->getStyle("B{$fila}")->getFont()->setUnderline(true);
            }

            $sheet->getStyle("A{$fila}:B{$fila}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($reporte->colorGrupo($d->grupo_operativo));

            $rangos[$d->grupo_operativo][0] ??= $fila;
            $rangos[$d->grupo_operativo][1] = $fila;
            $fila++;
        }

        return $rangos;
    }

    /**
     * Cuadro "RESUMEN POR GRUPO OPERATIVO" a la derecha del índice (K:P), con fórmulas sobre el índice.
     */
    private function escribirResumenGrupos(Worksheet $sheet, InsKardexReporte $reporte, array $rangos): void
    {
        $sheet->setCellValue('K2', 'RESUMEN POR GRUPO OPERATIVO');
        $sheet->getStyle('K2')->getFont()->setBold(true)->setSize(12);

        $encabezados = ['GRUPO OPERATIVO', 'PRODUCTOS', 'CANT. UNID. (SALDO UNIDADES)', 'COSTO (SALDO IMPORTE)', 'CONSUMO (SALIDAS IMPORTE)', 'TOTAL ENTRADAS IMPORTE'];
        foreach ($encabezados as $i => $texto) {
            $sheet->setCellValue(chr(ord('K') + $i) . '3', $texto);
        }
        $sheet->getStyle('K3:P3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '31869B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $fila = 4;
        $primera = $fila;
        foreach ($reporte->grupos_ordenados as $grupo) {
            [$ini, $fin] = $rangos[$grupo] ?? [null, null];
            $suma = fn(string $col) => $ini ? "=SUM({$col}{$ini}:{$col}{$fin})" : 0;

            $sheet->setCellValue("K{$fila}", InsKardexReporte::etiquetaGrupo($grupo));
            $sheet->setCellValue("L{$fila}", $ini ? "=COUNTA(A{$ini}:A{$fin})" : 0);
            $sheet->setCellValue("M{$fila}", $suma('H'));
            $sheet->setCellValue("N{$fila}", $suma('I'));
            $sheet->setCellValue("O{$fila}", $suma('G'));
            $sheet->setCellValue("P{$fila}", $suma('E'));
            $sheet->getStyle("K{$fila}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $reporte->colorGrupo($grupo)]],
            ]);
            $fila++;
        }
        $ultima = $fila - 1;

        $sheet->setCellValue("K{$fila}", 'TOTAL');
        $sheet->setCellValue("L{$fila}", "=SUM(L{$primera}:L{$ultima})");
        foreach (['M', 'N', 'O', 'P'] as $col) {
            $sheet->setCellValue("{$col}{$fila}", "=SUM({$col}{$primera}:{$col}{$ultima})");
        }
        $sheet->getStyle("K{$fila}:P{$fila}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DAEEF3']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        // Total costo = suma del costo (saldo importe) de todos los grupos, como en su hoja original
        $sheet->getStyle("N{$fila}")->getFont()->setSize(12);

        $sheet->getStyle("L4:L{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("O4:P{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_IMPORTE);
        $sheet->getStyle("M4:N{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_SALDO);
        $sheet->getStyle("K3:P{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("K3:P{$fila}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

        $pie = $fila + 2;
        $sheet->setCellValue("K{$pie}", 'Kardex ' . mb_strtoupper($reporte->tipo_kardex) . " {$reporte->anio} · generado "
            . now()->format('d/m/Y H:i'));
        $sheet->getStyle("K{$pie}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('7F7F7F');

        $sheet->getColumnDimension('K')->setWidth(22);
        $sheet->getColumnDimension('L')->setWidth(11);
        foreach (['M', 'N', 'O', 'P'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }
    }

    private function tituloHojaUnico(string $codigo, array $usados): string
    {
        $base = trim(str_replace(['[', ']', ':', '*', '?', '/', '\\', "'"], '', $codigo)) ?: 'KARDEX';
        $base = mb_substr($base, 0, 28);
        $titulo = $base;
        $n = 2;
        while (in_array(mb_strtoupper($titulo), array_map('mb_strtoupper', [...$usados, 'INDICE']), true)) {
            $titulo = "{$base}_{$n}";
            $n++;
        }
        return $titulo;
    }
}
