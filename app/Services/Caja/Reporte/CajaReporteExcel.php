<?php

namespace App\Services\Caja\Reporte;

use App\Services\Caja\Movimiento\CajaMovimientoConsulta;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel de caja de un mes o de un año, con las columnas de la hoja BASE, los colores de cada fila, el
 * resumen por clasificador y los arqueos. Solo lectura: no toca la base de datos.
 */
class CajaReporteExcel
{
    private const COLUMNAS = [
        'EMPRESA', 'N° DE CAJA', 'CONDICION', 'CATEGORIA', 'CODIGO', 'BENEFICIARIO', 'GASTOS  B+N', 'CLASIFICADOR 1',
        'CLASIFICADOR 2', 'SUB-GRUPO NG', 'SUB-GRUPO BL', 'M', 'FECHA', 'Semana', 'T. Doc', 'No. Doc',
        'Situación Cheque', 'IMPORTE $', 'IMPORTE S/', 'DISPONIBLE', 'Tipo', 'AÑO', 'MES', 'SEMANA', 'Solarizado',
        'GASTO DOLARIZADO', 'TC/',
    ];

    public function __construct(private CajaMovimientoConsulta $consulta)
    {
    }

    /** @return array{ruta: string, nombre: string} archivo temporal generado */
    public function generar(int $anio, ?int $mes = null): array
    {
        $datos = $this->consulta->listar(['anio' => $anio, 'mes' => $mes]);
        $libro = new Spreadsheet();

        $hoja = $libro->getActiveSheet()->setTitle('CAJA');
        $this->hojaMovimientos($hoja, $datos, $anio, $mes);

        $this->hojaResumen($libro->createSheet()->setTitle('RESUMEN'), $datos);
        $this->hojaArqueos($libro->createSheet()->setTitle('ARQUEOS'), $datos['desde'], $datos['hasta']);
        $libro->setActiveSheetIndex(0);

        $periodo = $mes ? Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F_Y') : (string) $anio;
        $nombre = 'CAJA_' . mb_strtoupper($periodo) . '.xlsx';
        $ruta = storage_path('app/temp/' . uniqid('caja_') . '.xlsx');
        if (!is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }
        (new Xlsx($libro))->save($ruta);

        return ['ruta' => $ruta, 'nombre' => $nombre];
    }

    private function hojaMovimientos(Worksheet $h, array $datos, int $anio, ?int $mes): void
    {
        $titulo = 'CAJA ' . ($mes ? mb_strtoupper(Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y')) : $anio);
        $h->setCellValue('A1', $titulo)->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $h->fromArray(self::COLUMNAS, null, 'A2');
        $h->getStyle('A2:AA2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $fila = 3;
        $h->fromArray(['', '', '', '', '', '', 'SALDO ANTERIOR', '', '', '', '', 'PEN', $datos['desde']], null, "A{$fila}");
        $h->setCellValue("T{$fila}", $datos['saldo_anterior']);
        $h->getStyle("A{$fila}:AA{$fila}")->getFont()->setBold(true)->setItalic(true);
        $fila++;

        foreach ($datos['filas'] as $m) {
            $h->fromArray([
                $m['empresa'], $m['numero_caja'], $m['condicion'], $m['categoria'], $m['codigo'], $m['beneficiario'],
                $m['descripcion'], $m['clasificador_1'], $m['clasificador_2'], $m['subgrupo_ng'], $m['subgrupo_bl'],
                $m['moneda'], null, $m['semana'], $m['tipo_documento'], $m['numero_documento'], $m['situacion_cheque'],
                $m['importe_usd'], $m['importe'], $m['disponible'], $m['tipo'], $m['anio'], $m['mes'], $m['semana_anio'],
                $m['importe'], $m['dolarizado'], $m['tipo_cambio'],
            ], null, "A{$fila}");
            $h->setCellValue("M{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($m['fecha'])));
            if ($m['importe_detalle']) {
                $h->getComment("S{$fila}")->getText()->createTextRun($m['importe_detalle']);
            }
            $estilo = $m['estilo'];
            $rango = "A{$fila}:AA{$fila}";
            if ($estilo['fondo']) {
                $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($estilo['fondo'], '#'));
            }
            if ($estilo['texto']) {
                $h->getStyle($rango)->getFont()->getColor()->setRGB(ltrim($estilo['texto'], '#'));
            }
            if ($estilo['negrita']) {
                $h->getStyle($rango)->getFont()->setBold(true);
            }
            $fila++;
        }

        $ultima = $fila - 1;
        $h->setCellValue("G{$fila}", 'TOTAL DEL PERIODO');
        $h->setCellValue("S{$fila}", "=SUM(S4:S{$ultima})");
        $h->setCellValue("T{$fila}", $datos['saldo_final']);
        $h->getStyle("A{$fila}:AA{$fila}")->getFont()->setBold(true);
        $h->getStyle("A{$fila}:AA{$fila}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        $h->getStyle("M3:M{$fila}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        foreach (['R', 'S', 'T', 'Y', 'Z'] as $col) {
            $h->getStyle("{$col}3:{$col}{$fila}")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        }
        $anchos = ['A' => 12, 'B' => 9, 'C' => 9, 'D' => 12, 'E' => 10, 'F' => 28, 'G' => 45, 'H' => 30, 'I' => 32, 'J' => 18,
            'K' => 16, 'L' => 6, 'M' => 11, 'N' => 8, 'O' => 16, 'P' => 18, 'Q' => 16, 'R' => 12, 'S' => 14, 'T' => 15,
            'U' => 9, 'V' => 6, 'W' => 5, 'X' => 8, 'Y' => 14, 'Z' => 14, 'AA' => 7];
        foreach ($anchos as $col => $ancho) {
            $h->getColumnDimension($col)->setWidth($ancho);
        }
        $h->freezePane('H3');
        $h->setAutoFilter("A2:AA{$ultima}");
    }

    private function hojaResumen(Worksheet $h, array $datos): void
    {
        $h->fromArray(['Saldo anterior', $datos['saldo_anterior']], null, 'A1');
        $h->fromArray(['Ingresos', $datos['totales']['ingresos']], null, 'A2');
        $h->fromArray(['Egresos', $datos['totales']['egresos']], null, 'A3');
        $h->fromArray(['Saldo final', $datos['saldo_final']], null, 'A4');
        $h->getStyle('A1:A4')->getFont()->setBold(true);

        $h->fromArray(['CLASIFICADOR 1', 'CLASIFICADOR 2', 'MOVIMIENTOS', 'INGRESOS', 'EGRESOS', 'NETO'], null, 'A6');
        $h->getStyle('A6:F6')->getFont()->setBold(true);
        $fila = 7;
        foreach ($this->consulta->totalesPorClasificador($datos['desde'], $datos['hasta']) as $t) {
            $h->fromArray([$t['clasificador_1'], $t['clasificador_2'], $t['movimientos'], $t['ingresos'], $t['egresos'],
                $t['ingresos'] + $t['egresos']], null, "A{$fila}");
            $fila++;
        }
        $h->getStyle("B1:B4")->getNumberFormat()->setFormatCode('#,##0.00');
        $h->getStyle("D7:F{$fila}")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        foreach (['A' => 40, 'B' => 45, 'C' => 13, 'D' => 15, 'E' => 15, 'F' => 15] as $col => $ancho) {
            $h->getColumnDimension($col)->setWidth($ancho);
        }
    }

    private function hojaArqueos(Worksheet $h, string $desde, string $hasta): void
    {
        $arqueos = $this->consulta->arqueos($desde, $hasta);
        $fuentes = collect($arqueos)->flatMap(fn($a) => array_column($a['detalles'], 'fuente'))->unique()->values()->all();
        $h->fromArray(array_merge(['FECHA'], $fuentes, ['TOTAL', 'DISPONIBLE', 'DIFERENCIA', 'OBSERVACIÓN']), null, 'A1');
        $h->getStyle('A1:' . $h->getHighestColumn() . '1')->getFont()->setBold(true);
        $fila = 2;
        foreach ($arqueos as $a) {
            $montos = array_column($a['detalles'], 'monto', 'fuente');
            $h->fromArray(array_merge(
                [Carbon::parse($a['fecha'])->format('d/m/Y')],
                array_map(fn($f) => $montos[$f] ?? null, $fuentes),
                [$a['total'], $a['disponible'], $a['diferencia'], $a['observacion']]
            ), null, "A{$fila}");
            $fila++;
        }
        $h->getStyle("B2:" . $h->getHighestColumn() . $fila)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        foreach (range('A', $h->getHighestColumn()) as $col) {
            $h->getColumnDimension($col)->setWidth(16);
        }
    }
}
