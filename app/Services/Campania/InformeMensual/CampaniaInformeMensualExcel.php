<?php

namespace App\Services\Campania\InformeMensual;

use App\Models\CampaniaInformeMensual;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel del informe mensual de campaña. Sin plantilla de celdas fijas: cada bloque es una tabla independiente, una
 * debajo de otra, con su título y sus columnas. Las listas largas van en la hoja "Detalle" para no empujar el resumen.
 * El diseño (colores, anchos, títulos) se cambia aquí y en CampaniaInformeMensualFormatoReglas.
 */
class CampaniaInformeMensualExcel
{
    private const COLOR_TITULO = '1F4E78';
    private const COLOR_ENCABEZADO = 'D9E1F2';
    private const COLOR_SECCION = 'BDD7EE';
    private const COLOR_GRUPO = 'EDF2F9';
    private const COLOR_TOTAL = 'FFE699';
    private const COLOR_ERROR = 'F8CBAD';
    private const COLOR_AVISO = 'FFF2CC';

    private const FORMATOS = [
        'numero' => '#,##0.00;-#,##0.00;"-"',
        'cantidad' => '#,##0.000;-#,##0.000;"-"',
        'entero' => '#,##0;-#,##0;"-"',
        'usd' => '"$" #,##0.00;-"$" #,##0.00;"-"',
        'soles' => '"S/" #,##0.00;-"S/" #,##0.00;"-"',
        'pct' => '0.00%;-0.00%;"-"',
        'fecha' => 'dd/mm/yyyy',
    ];

    /** @var array<string, string> ancla => 'Hoja'!celda */
    private array $anclas = [];
    /** @var array<int, array{0: Worksheet, 1: string, 2: string}> celdas con enlace pendiente a un ancla */
    private array $enlaces = [];

    public function generar(CampaniaInformeMensual $informe): Spreadsheet
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator(\App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre())->setTitle($this->titulo($informe));
        $this->anclas = [];
        $this->enlaces = [];
        $tablas = CampaniaInformeMensualFormatoReglas::tablas($informe->datos + ['advertencias' => $informe->advertenciasVigentes()]);

        // Hojas: Resumen y Detalle siempre; Evaluaciones si hay evaluaciones con detalle
        $usadas = array_unique(array_merge([CampaniaInformeMensualFormatoReglas::HOJA_RESUMEN, CampaniaInformeMensualFormatoReglas::HOJA_DETALLE], array_column($tablas, 'hoja')));
        $hojas = [];
        foreach (CampaniaInformeMensualFormatoReglas::HOJAS as $clave => $nombre) {
            if (!in_array($clave, $usadas, true)) {
                continue;
            }
            $hojas[$clave] = $hojas ? $libro->createSheet()->setTitle($nombre) : $libro->getActiveSheet()->setTitle($nombre);
            $this->encabezado($hojas[$clave], $informe, $clave === CampaniaInformeMensualFormatoReglas::HOJA_RESUMEN ? null : $nombre);
        }

        $fila = array_fill_keys(array_keys($hojas), 4);
        $maxColumnas = array_fill_keys(array_keys($hojas), 1);
        foreach ($tablas as $tabla) {
            $clave = $tabla['hoja'];
            $fila[$clave] = $this->tabla($hojas[$clave], $tabla, $fila[$clave]) + 1;
            $maxColumnas[$clave] = max($maxColumnas[$clave], count($tabla['columnas']));
        }

        // Enlaces internos: un valor del resumen lleva a su fila en otra hoja (p. ej. la evaluación en "Evaluaciones")
        foreach ($this->enlaces as [$hoja, $celda, $ancla]) {
            if (isset($this->anclas[$ancla])) {
                $hoja->getCell($celda)->getHyperlink()->setUrl('sheet://' . $this->anclas[$ancla])->setTooltip('Ver el detalle de esta evaluación');
                $hoja->getStyle($celda)->getFont()->setUnderline(true)->getColor()->setRGB('0563C1');
            }
        }

        // Anchos: la primera columna lleva los nombres (rubros, productos); en detalle la primera es la fecha
        $this->anchos($hojas[CampaniaInformeMensualFormatoReglas::HOJA_RESUMEN], $maxColumnas[CampaniaInformeMensualFormatoReglas::HOJA_RESUMEN], 46);
        $this->anchos($hojas[CampaniaInformeMensualFormatoReglas::HOJA_DETALLE], $maxColumnas[CampaniaInformeMensualFormatoReglas::HOJA_DETALLE], 12, 38);
        if (isset($hojas[CampaniaInformeMensualFormatoReglas::HOJA_EVALUACIONES])) {
            $this->anchos($hojas[CampaniaInformeMensualFormatoReglas::HOJA_EVALUACIONES], $maxColumnas[CampaniaInformeMensualFormatoReglas::HOJA_EVALUACIONES], 14);
        }
        foreach ($hojas as $hoja) {
            $hoja->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
            $hoja->setShowGridlines(false);
        }
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    public function nombreArchivo(CampaniaInformeMensual $informe): string
    {
        $c = $informe->datos['ficha'];
        $tipo = $informe->es_cierre ? 'CIERRE' : 'MENSUAL';
        $sufijo = $informe->exists ? "v{$informe->version}" : 'CONSULTA_' . now()->format('Ymd');
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', "INFORME_{$tipo}_{$informe->periodo}_CAMPO_{$c['campo']}_{$c['campania']}_{$sufijo}") . '.xlsx';
    }

    private function titulo(CampaniaInformeMensual $informe): string
    {
        $c = $informe->datos['ficha'];
        $mes = Carbon::create($informe->anio, $informe->mes, 1)->locale('es')->isoFormat('MMMM YYYY');
        return mb_strtoupper(($informe->es_cierre ? 'Informe de cierre' : 'Informe mensual') . " – {$mes} – Campo {$c['campo']} – Campaña {$c['campania']}");
    }

    private function encabezado(Worksheet $hoja, CampaniaInformeMensual $informe, ?string $sufijo = null): void
    {
        $hoja->setCellValue('A1', $this->titulo($informe) . ($sufijo ? " – {$sufijo}" : ''));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::COLOR_TITULO);
        $c = $informe->datos['ficha'];
        $hoja->setCellValue('A2', 'Acumulado del ' . Carbon::parse($c['fecha_inicio'])->format('d/m/Y') . ' al ' . Carbon::parse($c['fecha_corte'])->format('d/m/Y')
            . ($informe->exists
                ? " · Versión {$informe->version} · Generado el " . $informe->updated_at->format('d/m/Y H:i') . ($informe->generadoPor ? " por {$informe->generadoPor->name}" : '')
                : ' · Consulta al ' . now()->format('d/m/Y H:i') . ' (no guardado)'));
        $hoja->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('595959');
    }

    /** Escribe una tabla desde $fila y devuelve la primera fila libre. */
    private function tabla(Worksheet $hoja, array $tabla, int $fila): int
    {
        $n = count($tabla['columnas']);
        // Los avisos son texto largo: la columna "Detalle" se extiende hasta la K
        $ancho = $tabla['clave'] === 'advertencias' ? 11 : $n;
        $ultima = Coordinate::stringFromColumnIndex($ancho);

        $hoja->setCellValue("A{$fila}", mb_strtoupper($tabla['titulo']));
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_TITULO]],
        ]);
        $fila++;

        foreach ($tabla['columnas'] as $i => $col) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $fila, trim($col['titulo']));
        }
        if ($ancho > $n) {
            $hoja->mergeCells(Coordinate::stringFromColumnIndex($n) . "{$fila}:{$ultima}{$fila}");
        }
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_ENCABEZADO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $inicioDatos = $fila;
        $fila++;

        if (!$tabla['filas']) {
            $hoja->setCellValue("A{$fila}", $tabla['vacio']);
            $hoja->mergeCells("A{$fila}:{$ultima}{$fila}");
            $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->getColor()->setRGB('808080');
            $fila++;
        }
        foreach ($tabla['filas'] as $f) {
            if (isset($f['ancla'])) {
                $this->anclas[$f['ancla']] = "'" . $hoja->getTitle() . "'!" . Coordinate::stringFromColumnIndex(((int) ($f['ancla_col'] ?? 0)) + 1) . $fila;
            }
            foreach ($f['enlaces'] ?? [] as $col => $enlace) {
                if (!empty($enlace['ancla'])) {
                    $this->enlaces[] = [$hoja, Coordinate::stringFromColumnIndex($col + 1) . $fila, $enlace['ancla']];
                }
            }
            foreach ($f['celdas'] as $i => $valor) {
                $tipo = $tabla['columnas'][$i]['tipo'] ?? 'texto';
                $celda = Coordinate::stringFromColumnIndex($i + 1) . $fila;
                if ($valor === null || $valor === '') {
                    continue;
                }
                if ($tipo === 'fecha') {
                    $hoja->setCellValue($celda, ExcelDate::PHPToExcel(Carbon::parse($valor)->startOfDay()));
                } elseif ($tipo === 'texto') {
                    $hoja->setCellValueExplicit($celda, (string) $valor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $hoja->setCellValue($celda, is_numeric($valor) ? (float) $valor : $valor);
                }
                if (isset(self::FORMATOS[$tipo])) {
                    $hoja->getStyle($celda)->getNumberFormat()->setFormatCode(self::FORMATOS[$tipo]);
                }
            }
            $color = ['seccion' => self::COLOR_SECCION, 'grupo' => self::COLOR_GRUPO, 'total' => self::COLOR_TOTAL,
                'error' => self::COLOR_ERROR, 'aviso' => self::COLOR_AVISO][$f['estilo']] ?? null;
            if ($color) {
                $estilo = $hoja->getStyle("A{$fila}:{$ultima}{$fila}");
                $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                $estilo->getFont()->setBold(in_array($f['estilo'], ['seccion', 'total'], true));
            }
            if ($ancho > $n) {
                $hoja->mergeCells(Coordinate::stringFromColumnIndex($n) . "{$fila}:{$ultima}{$fila}");
                $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $hoja->getRowDimension($fila)->setRowHeight(15 * max(1, ceil(mb_strlen((string) end($f['celdas'])) / 150)));
            }
            $fila++;
        }
        $hoja->getStyle("A{$inicioDatos}:{$ultima}" . ($fila - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');

        if ($tabla['nota']) {
            $hoja->setCellValue("A{$fila}", $tabla['nota']);
            $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('595959');
            $fila++;
        }
        return $fila;
    }

    private function anchos(Worksheet $hoja, int $columnas, float $primera, ?float $segunda = null): void
    {
        $hoja->getColumnDimension('A')->setWidth($primera);
        for ($i = 2; $i <= max($columnas, 2); $i++) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($i === 2 && $segunda ? $segunda : 15);
        }
    }
}
