<?php

namespace App\Services\Cochinilla\Excel;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escribe y lee el Excel oficial de cochinilla de un año (ver CochinillaExcelReglas). Sin columnas ocultas ni fórmulas
 * pesadas: solo la suma de cada lote y la basura (kilos − lo clasificado) como fórmulas simples.
 *
 * Estructura de datos (la misma para convertir, exportar e importar):
 *   ingreso:     [['lote'=>?int, 'sublote'=>?string, 'fecha'=>Y-m-d, 'campo', 'observacion'(texto), 'kilos', 'campania'?], …]
 *                filas de sublote (lote null) seguidas de la fila total del lote (sublote null)
 *   venteado:    [['lote','fecha','kilos','limpia','basura','polvillo'], …]
 *   filtrado:    [['lote','fecha','kilos','primera','segunda','tercera','piedra','basura'], …]
 *   infestacion: [['tipo','fecha','campo','kg_madres','origen','metodo','envases','capacidad'], …]
 *   extras:      ['Despachos' => ['encabezados' => [...], 'filas' => [[...], …]], …]
 */
class CochinillaExcelLibro
{
    private const FILAS_LIBRES = 400; // filas con fórmula/validación preparadas para seguir llenando

    public function crear(int $anio, array $datos, array $listas): Spreadsheet
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setTitle("Cochinilla {$anio}")->setCreator(\App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre());
        $this->ingreso($libro->getActiveSheet()->setTitle(CochinillaExcelReglas::HOJA_INGRESO), $datos['ingreso'] ?? []);
        $this->venteado($libro->createSheet()->setTitle(CochinillaExcelReglas::HOJA_VENTEADO), $datos['venteado'] ?? []);
        $this->filtrado($libro->createSheet()->setTitle(CochinillaExcelReglas::HOJA_FILTRADO), $datos['filtrado'] ?? []);
        // Mismo orden de pestañas que el Excel que usan hoy
        $extra = fn(string $n) => $this->extra($libro->createSheet()->setTitle($n), $datos['extras'][$n] ?? ['encabezados' => [], 'filas' => []]);
        $extra('Cosecha Mamas');
        $this->infestacion($libro->createSheet()->setTitle(CochinillaExcelReglas::HOJA_INFESTACION), $datos['infestacion'] ?? []);
        $this->resumen($libro->createSheet()->setTitle(CochinillaExcelReglas::HOJA_RESUMEN), $datos);
        $extra('PENCA');
        $rangos = $this->listas($libro->createSheet()->setTitle(CochinillaExcelReglas::HOJA_LISTAS), $listas);
        $this->validaciones($libro, $rangos);
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    // ------------------------------------------------------------------ escritura

    private function ingreso(Worksheet $h, array $filas): void
    {
        $this->encabezado($h, CochinillaExcelReglas::COLUMNAS_INGRESO);
        $fila = 2;
        $inicioLote = 2;
        foreach ($filas as $r) {
            if ($r['lote'] === null) {
                // Sublote: texto, para que 1457.10 no se vuelva 1457.1
                $h->setCellValueExplicit("B{$fila}", (string) $r['sublote'], DataType::TYPE_STRING);
            } else {
                $h->setCellValue("A{$fila}", $r['lote']);
                $h->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true);
                $h->getStyle("A{$fila}:G{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
            }
            $h->setCellValue("C{$fila}", $r['fecha'] ? ExcelDate::PHPToExcel($r['fecha']) : null);
            $h->setCellValueExplicit("D{$fila}", (string) $r['campo'], DataType::TYPE_STRING);
            $h->setCellValue("E{$fila}", $r['observacion']);
            // Total del lote = suma de sus sublotes (si tiene)
            $h->setCellValue("F{$fila}", $r['lote'] !== null && $fila > $inicioLote ? "=SUM(F{$inicioLote}:F" . ($fila - 1) . ')' : $r['kilos']);
            $h->setCellValue("G{$fila}", $r['campania'] ?? null);
            $fila++;
            if ($r['lote'] !== null) {
                $inicioLote = $fila;
            }
        }
        $this->formatos($h, $fila, ['C' => 'dd/mm/yyyy', 'F' => '#,##0.00'], ['A' => 9, 'B' => 10, 'C' => 12, 'D' => 10, 'E' => 26, 'F' => 12, 'G' => 22]);
        $h->getStyle('G1:G' . ($fila + self::FILAS_LIBRES))->getFont()->getColor()->setRGB('6B7280');
    }

    private function venteado(Worksheet $h, array $filas): void
    {
        $this->encabezado($h, CochinillaExcelReglas::COLUMNAS_VENTEADO);
        $fila = 2;
        foreach ($filas as $r) {
            $h->fromArray([$r['lote'], $r['fecha'] ? ExcelDate::PHPToExcel($r['fecha']) : null, $r['kilos'], $r['limpia'], null, $r['polvillo']], null, "A{$fila}", true);
            $fila++;
        }
        // Basura = kilos ingresados − limpia − polvillo (también en filas libres)
        for ($f = 2; $f < $fila + self::FILAS_LIBRES; $f++) {
            $h->setCellValue("E{$f}", "=IF(C{$f}=\"\",\"\",ROUND(C{$f}-D{$f}-F{$f},2))");
        }
        $this->formatos($h, $fila, ['B' => 'dd/mm/yyyy', 'C' => '#,##0.00', 'D' => '#,##0.00', 'E' => '#,##0.00', 'F' => '#,##0.00'], ['A' => 9, 'B' => 13, 'C' => 15, 'D' => 12, 'E' => 12, 'F' => 12]);
    }

    private function filtrado(Worksheet $h, array $filas): void
    {
        $this->encabezado($h, CochinillaExcelReglas::COLUMNAS_FILTRADO);
        $fila = 2;
        foreach ($filas as $r) {
            $h->fromArray([$r['lote'], $r['fecha'] ? ExcelDate::PHPToExcel($r['fecha']) : null, $r['kilos'], $r['primera'], $r['segunda'], $r['tercera'], $r['piedra']], null, "A{$fila}", true);
            $fila++;
        }
        for ($f = 2; $f < $fila + self::FILAS_LIBRES; $f++) {
            $h->setCellValue("H{$f}", "=IF(C{$f}=\"\",\"\",ROUND(C{$f}-D{$f}-E{$f}-F{$f}-G{$f},2))");
        }
        $this->formatos($h, $fila, ['B' => 'dd/mm/yyyy', 'C' => '#,##0.00', 'D' => '#,##0.00', 'E' => '#,##0.00', 'F' => '#,##0.00', 'G' => '#,##0.00', 'H' => '#,##0.00'],
            ['A' => 9, 'B' => 13, 'C' => 15, 'D' => 11, 'E' => 11, 'F' => 11, 'G' => 11, 'H' => 11]);
    }

    /**
     * Propuesta 2 (INFESTACIONES.xlsx): Tipo | Fecha | Campo | Área | Campaña | Kg madres | Kg/ha | Origen | Método |
     * Und x envase | Envases | Infestadores | Madres/infest. | Infest./ha | % Malla | Procedencia. Área y campaña las pone
     * el sistema; kg/ha, infestadores y sus relaciones son fórmulas.
     */
    private function infestacion(Worksheet $h, array $filas): void
    {
        $this->encabezado($h, CochinillaExcelReglas::COLUMNAS_INFESTACION);
        $fila = 2;
        foreach ($filas as $r) {
            $h->setCellValue("A{$fila}", mb_strtoupper((string) $r['tipo']));
            $h->setCellValue("B{$fila}", $r['fecha'] ? ExcelDate::PHPToExcel($r['fecha']) : null);
            $h->setCellValueExplicit("C{$fila}", (string) $r['campo'], DataType::TYPE_STRING);
            $h->setCellValue("D{$fila}", $r['area'] ?? null);
            $h->setCellValue("E{$fila}", $r['campania'] ?? null);
            $h->setCellValue("F{$fila}", $r['kg_madres']);
            $h->setCellValueExplicit("H{$fila}", (string) $r['origen'], DataType::TYPE_STRING);
            $h->setCellValue("I{$fila}", mb_strtoupper((string) $r['metodo']));
            $h->setCellValue("J{$fila}", $r['capacidad']);
            $h->setCellValue("K{$fila}", $r['envases']);
            $h->setCellValue("O{$fila}", $r['malla'] ?? null);
            $h->setCellValue("P{$fila}", $r['procedencia'] ?? null);
            $fila++;
        }
        for ($f = 2; $f < $fila + self::FILAS_LIBRES; $f++) {
            $h->setCellValue("G{$f}", "=IF(OR(F{$f}=\"\",N(D{$f})=0),\"\",F{$f}/D{$f})");
            $h->setCellValue("L{$f}", "=IF(K{$f}=\"\",\"\",J{$f}*K{$f})");
            $h->setCellValue("M{$f}", "=IF(N(L{$f})=0,\"\",F{$f}/L{$f})");
            $h->setCellValue("N{$f}", "=IF(OR(N(L{$f})=0,N(D{$f})=0),\"\",L{$f}/D{$f})");
        }
        $this->formatos($h, $fila, ['B' => 'dd/mm/yyyy', 'D' => '0.000', 'F' => '#,##0.00', 'G' => '#,##0.00', 'L' => '#,##0', 'M' => '0.000000', 'N' => '#,##0', 'O' => '0.00'],
            ['A' => 15, 'B' => 12, 'C' => 9, 'D' => 8, 'E' => 11, 'F' => 11, 'G' => 10, 'H' => 10, 'I' => 9, 'J' => 10, 'K' => 9, 'L' => 12, 'M' => 11, 'N' => 12, 'O' => 10, 'P' => 18]);
        $h->getStyle('D2:E' . ($fila + self::FILAS_LIBRES))->getFont()->getColor()->setRGB('6B7280');
    }

    /**
     * RESUMEN: consolidado por lote (lo que hacía la hoja RESUMEN del histórico con fórmulas), armado con los datos del
     * propio archivo en cada descarga. No se importa.
     */
    private function resumen(Worksheet $h, array $datos): void
    {
        $this->encabezado($h, CochinillaExcelReglas::COLUMNAS_RESUMEN);
        $vent = collect($datos['venteado'] ?? [])->groupBy('lote');
        $filt = collect($datos['filtrado'] ?? [])->groupBy('lote');
        $basura = fn($x, array $cols) => round($x->sum(fn($y) => (float) $y['kilos'] - collect($cols)->sum(fn($c) => (float) $y[$c])), 2);
        $fila = 2;
        $subs = 0;
        foreach ($datos['ingreso'] ?? [] as $r) {
            if ($r['lote'] === null) {
                $subs++;
                continue;
            }
            $v = $vent->get($r['lote'], collect());
            $f = $filt->get($r['lote'], collect());
            $h->fromArray([
                $r['lote'], $r['fecha'] ? ExcelDate::PHPToExcel($r['fecha']) : null, $r['campo'], $r['campania'] ?? null, $r['observacion'],
                (float) $r['kilos'], $subs ?: null,
                $v->sum('kilos') ?: null, $v->sum('limpia') ?: null, $v->isNotEmpty() ? $basura($v, ['limpia', 'polvillo']) : null, $v->sum('polvillo') ?: null,
                $f->sum('kilos') ?: null, $f->sum('primera') ?: null, $f->sum('segunda') ?: null, $f->sum('tercera') ?: null, $f->sum('piedra') ?: null,
                $f->isNotEmpty() ? $basura($f, ['primera', 'segunda', 'tercera', 'piedra']) : null,
            ], null, "A{$fila}", true);
            if ($f->sum('kilos') > 0) {
                $h->setCellValue("R{$fila}", "=IF(L{$fila}>0,M{$fila}/L{$fila},\"\")");
            }
            $subs = 0;
            $fila++;
        }
        if ($fila > 2) {
            $h->setCellValue("A{$fila}", 'TOTAL');
            foreach (['F', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'] as $col) {
                $h->setCellValue("{$col}{$fila}", "=SUM({$col}2:{$col}" . ($fila - 1) . ')');
            }
            $h->getStyle("A{$fila}:R{$fila}")->getFont()->setBold(true);
        }
        $h->getStyle("B2:B{$fila}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $h->getStyle("F2:Q{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("R2:R{$fila}")->getNumberFormat()->setFormatCode('0.0%');
        foreach (range('A', 'R') as $col) {
            $h->getColumnDimension($col)->setWidth($col === 'E' ? 22 : 11);
        }
    }

    private function extra(Worksheet $h, array $hoja): void
    {
        $h->setCellValue('A1', 'Hoja que se guarda con el archivo (el sistema aún no la importa: solo la muestra al subir).');
        $h->getStyle('A1')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');
        if (!$hoja['encabezados']) {
            return;
        }
        $h->fromArray($hoja['encabezados'], null, 'A2');
        $rango = 'A2:' . Coordinate::stringFromColumnIndex(count($hoja['encabezados'])) . '2';
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
        $h->getStyle($rango)->getAlignment()->setWrapText(true);
        $fila = 3;
        foreach ($hoja['filas'] as $r) {
            foreach (array_values($r) as $i => $v) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                if (is_array($v) && isset($v['fecha'])) {
                    $h->setCellValue("{$col}{$fila}", ExcelDate::PHPToExcel($v['fecha']));
                    $h->getStyle("{$col}{$fila}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                } else {
                    $h->setCellValue("{$col}{$fila}", $v);
                }
            }
            $fila++;
        }
        foreach (range(1, count($hoja['encabezados'])) as $i) {
            $h->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(13);
        }
        $h->freezePane('A3');
    }

    /** @return array<string, string> lista => rango */
    private function listas(Worksheet $h, array $listas): array
    {
        $rangos = [];
        $col = 1;
        foreach ($listas as $clave => [$titulo, $valores]) {
            $letra = Coordinate::stringFromColumnIndex($col);
            $h->setCellValue("{$letra}1", $titulo);
            $fila = 2;
            foreach ($valores as $v) {
                $h->setCellValueExplicit("{$letra}{$fila}", (string) $v, DataType::TYPE_STRING);
                $fila++;
            }
            $rangos[$clave] = "Listas!\${$letra}\$2:\${$letra}\$" . max(2, $fila - 1);
            $h->getColumnDimension($letra)->setWidth(28);
            $col++;
        }
        $h->getStyle('1:1')->getFont()->setBold(true);
        return $rangos;
    }

    private function validaciones(Spreadsheet $libro, array $rangos): void
    {
        $lista = function (Worksheet $h, string $rango, string $formula) {
            $v = new DataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setShowDropDown(true)->setAllowBlank(true)->setShowErrorMessage(false)->setFormula1($formula);
            $h->setDataValidation($rango, $v);
        };
        $hasta = 6000;
        $ingreso = $libro->getSheetByName(CochinillaExcelReglas::HOJA_INGRESO);
        $lista($ingreso, "D2:D{$hasta}", $rangos['campos']);
        $lista($ingreso, "E2:E{$hasta}", $rangos['observaciones']);
        $inf = $libro->getSheetByName(CochinillaExcelReglas::HOJA_INFESTACION);
        $lista($inf, "A2:A{$hasta}", '"' . mb_strtoupper(implode(',', CochinillaExcelReglas::TIPOS_INFESTACION)) . '"');
        $lista($inf, "C2:C{$hasta}", $rangos['campos']);
        $lista($inf, "H2:H{$hasta}", $rangos['campos']);
        $lista($inf, "I2:I{$hasta}", '"' . mb_strtoupper(implode(',', CochinillaExcelReglas::METODOS)) . '"');
    }

    private function encabezado(Worksheet $h, array $columnas): void
    {
        $h->fromArray($columnas, null, 'A1');
        $rango = 'A1:' . Coordinate::stringFromColumnIndex(count($columnas)) . '1';
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
        $h->freezePane('A2');
    }

    private function formatos(Worksheet $h, int $fila, array $formatos, array $anchos): void
    {
        $hasta = $fila + self::FILAS_LIBRES;
        foreach ($formatos as $col => $formato) {
            $h->getStyle("{$col}2:{$col}{$hasta}")->getNumberFormat()->setFormatCode($formato);
        }
        foreach ($anchos as $col => $ancho) {
            $h->getColumnDimension($col)->setWidth($ancho);
        }
    }

    // ------------------------------------------------------------------ lectura

    public function leer(string $ruta): array
    {
        $lector = IOFactory::createReaderForFile($ruta);
        $libro = $lector->load($ruta);
        $hoja = fn($n) => $libro->getSheetByName($n);
        if (!$hoja(CochinillaExcelReglas::HOJA_INGRESO)) {
            throw new \App\Exceptions\ErrorControlado('Este Excel no tiene el formato oficial de cochinilla (falta la hoja "Ingreso"). Descarga el archivo del año desde el sistema.');
        }

        $datos = ['ingreso' => [], 'venteado' => [], 'filtrado' => [], 'infestacion' => [], 'extras' => []];
        foreach ($this->filas($hoja(CochinillaExcelReglas::HOJA_INGRESO), 7) as $n => $v) {
            $datos['ingreso'][] = ['fila' => $n, 'lote' => $v[0], 'sublote' => $v[1], 'fecha' => $this->fecha($v[2]), 'campo' => $this->texto($v[3]),
                'observacion' => $this->texto($v[4]), 'kilos' => $v[5]];
        }
        if ($h = $hoja(CochinillaExcelReglas::HOJA_VENTEADO)) {
            foreach ($this->filas($h, 6, [4]) as $n => $v) {
                $datos['venteado'][] = ['fila' => $n, 'lote' => $v[0], 'fecha' => $this->fecha($v[1]), 'kilos' => $v[2], 'limpia' => $v[3], 'basura' => $v[4], 'polvillo' => $v[5]];
            }
        }
        if ($h = $hoja(CochinillaExcelReglas::HOJA_FILTRADO)) {
            foreach ($this->filas($h, 8, [7]) as $n => $v) {
                $datos['filtrado'][] = ['fila' => $n, 'lote' => $v[0], 'fecha' => $this->fecha($v[1]), 'kilos' => $v[2], 'primera' => $v[3],
                    'segunda' => $v[4], 'tercera' => $v[5], 'piedra' => $v[6], 'basura' => $v[7]];
            }
        }
        if ($h = $hoja(CochinillaExcelReglas::HOJA_INFESTACION)) {
            // Área, campaña y las columnas de fórmula (kg/ha, infestadores…) no cuentan como datos de la fila
            foreach ($this->filas($h, 16, [3, 4, 6, 11, 12, 13]) as $n => $v) {
                $datos['infestacion'][] = ['fila' => $n, 'tipo' => mb_strtolower((string) $this->texto($v[0])), 'fecha' => $this->fecha($v[1]),
                    'campo' => $this->texto($v[2]), 'kg_madres' => $v[5], 'origen' => $this->texto($v[7]), 'metodo' => mb_strtolower((string) $this->texto($v[8])),
                    'capacidad' => $v[9], 'envases' => $v[10], 'malla' => is_numeric($v[14]) ? (float) $v[14] : null, 'procedencia' => $this->texto($v[15])];
            }
        }
        foreach (array_keys(CochinillaExcelReglas::HOJAS_EXTRA) as $nombre) {
            $h = $hoja($nombre);
            if (!$h) {
                continue;
            }
            $ultimaCol = Coordinate::columnIndexFromString($h->getHighestDataColumn());
            $enc = [];
            for ($c = 1; $c <= $ultimaCol; $c++) {
                $enc[] = $this->texto($h->getCell([$c, 2])->getValue());
            }
            $filas = [];
            for ($f = 3; $f <= $h->getHighestDataRow(); $f++) {
                $fila = [];
                for ($c = 1; $c <= $ultimaCol; $c++) {
                    $celda = $h->getCell([$c, $f]);
                    $v = $celda->getCalculatedValue();
                    $fila[] = ExcelDate::isDateTime($celda) && is_numeric($v) ? ['fecha' => Carbon::instance(ExcelDate::excelToDateTimeObject($v))->toDateString()] : $v;
                }
                if (array_filter($fila, fn($x) => $x !== null && $x !== '')) {
                    $filas[] = $fila;
                }
            }
            $datos['extras'][$nombre] = ['encabezados' => $enc, 'filas' => $filas];
        }
        $libro->disconnectWorksheets();
        return $datos;
    }

    /**
     * Filas con datos de una hoja (desde la 2), con el valor calculado de cada celda. Las columnas de fórmula
     * ($soloFormula) no cuentan para decidir si la fila tiene datos (la basura vacía de las filas libres).
     *
     * @return array<int, array> número de fila => valores
     */
    private function filas(Worksheet $h, int $columnas, array $soloFormula = []): array
    {
        $resultado = [];
        $ultima = $h->getHighestDataRow();
        for ($f = 2; $f <= $ultima; $f++) {
            $v = [];
            $tieneDatos = false;
            for ($c = 1; $c <= $columnas; $c++) {
                $celda = $h->getCell([$c, $f]);
                $valor = $celda->getCalculatedValue();
                $v[] = $valor === '' ? null : $valor;
                if ($valor !== null && $valor !== '' && !in_array($c - 1, $soloFormula, true) && $c <= $columnas) {
                    $tieneDatos = true;
                }
            }
            if ($tieneDatos) {
                $resultado[$f] = $v;
            }
        }
        return $resultado;
    }

    private function texto($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v) && floor($v) == $v) {
            $v = (string) (int) $v;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    /** Fecha: serie de Excel o texto dd/mm/aaaa. Devuelve Y-m-d, "invalida:…" o null. */
    public function fecha($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return $v > 0 ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString() : 'invalida:' . $v;
        }
        $v = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            [$y, $mes, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $v, $m)) {
            [$d, $mes, $y] = [(int) $m[1], (int) $m[2], (int) (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3])];
        } else {
            return 'invalida:' . $v;
        }
        return checkdate($mes, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mes, $d) : 'invalida:' . $v;
    }
}
