<?php

namespace App\Services\Reporte\HistorialLaboral;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel del historial laboral: "Resumen" (periodo, identidades, totales, horas por labor y campo, producción contra el
 * estándar), "Día a día" (una fila por tramo, con el texto explicado del día) y "Producción" (cada actividad con sus
 * recojos y el % del estándar).
 */
class ReporteHistorialLaboralExcel
{
    public function generar(array $historial, array $identidades, string $desde, string $hasta): Spreadsheet
    {
        $libro = new Spreadsheet();
        $this->resumen($libro->getActiveSheet()->setTitle('Resumen'), $historial['resumen'], $identidades, $desde, $hasta);
        $this->diario($libro->createSheet()->setTitle('Día a día'), $historial['dias']);
        $this->produccion($libro->createSheet()->setTitle('Producción'), $historial['dias']);
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    private function resumen(Worksheet $h, array $r, array $identidades, string $desde, string $hasta): void
    {
        $h->setCellValue('A1', 'Historial laboral');
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $h->setCellValue('A2', 'Del ' . Carbon::parse($desde)->format('d/m/Y') . ' al ' . Carbon::parse($hasta)->format('d/m/Y')
            . ' · generado el ' . now()->format('d/m/Y H:i'));
        $fila = 4;
        $h->setCellValue("A{$fila}", 'Registrado como');
        $h->getStyle("A{$fila}")->getFont()->setBold(true);
        foreach ($identidades as $i) {
            $fila++;
            $h->setCellValue("A{$fila}", $i['nombre']);
            $h->setCellValue("B{$fila}", $i['detalle'] . ($i['documento'] ? " · DNI {$i['documento']}" : ''));
        }

        $fila += 2;
        $datos = [
            ['Días trabajados en planilla', $r['dias_planilla']],
            ['Horas en planilla', $r['horas_planilla']],
            ['Días en cuadrilla', $r['dias_cuadrilla']],
            ['Horas en cuadrilla', $r['horas_cuadrilla']],
            ['Días con planilla y cuadrilla el mismo día (posible trabajo extra)', $r['dias_doble']],
            ['Costo planilla (BDD)', $r['costo_planilla']],
            ['Costo cuadrilla (jornal + bonos)', $r['costo_cuadrilla']],
        ];
        foreach ($r['ausencias'] as $tipo => $n) {
            $datos[] = ["Días de {$tipo}", $n];
        }
        foreach ($datos as [$etiqueta, $valor]) {
            $h->setCellValue("A{$fila}", $etiqueta);
            $h->setCellValue("B{$fila}", $valor);
            $fila++;
        }

        $fila++;
        $this->encabezado($h, $fila, ['Labor', 'Horas', 'Veces']);
        $inicio = ++$fila;
        foreach ($r['por_labor'] as $l) {
            $h->fromArray([$l['labor'], $l['horas'], $l['veces']], null, "A{$fila}");
            $fila++;
        }
        $h->setCellValue("A{$fila}", 'Total');
        $h->setCellValue("B{$fila}", $fila > $inicio ? "=SUM(B{$inicio}:B" . ($fila - 1) . ')' : 0);
        $h->getStyle("A{$fila}:C{$fila}")->getFont()->setBold(true);

        $fila += 2;
        $this->encabezado($h, $fila, ['Campo', 'Horas']);
        $fila++;
        foreach ($r['por_campo'] as $c) {
            $h->fromArray([$c['campo'], $c['horas']], null, "A{$fila}");
            $fila++;
        }

        $fila++;
        $p = $r['produccion'];
        $h->setCellValue("A{$fila}", "Producción contra el estándar: {$p['evaluadas']} actividades, {$p['sobre']} sobre, {$p['debajo']} debajo"
            . ($p['promedio_pct'] !== null ? ", promedio {$p['promedio_pct']} % del estándar" : ''));
        $h->getStyle("A{$fila}")->getFont()->setBold(true);
        $fila++;
        $this->encabezado($h, $fila, ['Labor', 'Veces', 'Sobre', 'Debajo', 'Producción promedio', 'Estándar', '% promedio']);
        $fila++;
        foreach ($p['por_labor'] as $l) {
            $h->fromArray([$l['labor'], $l['veces'], $l['sobre'], $l['debajo'], $l['produccion_promedio'] . ' ' . $l['unidades'], $l['estandar'], $l['promedio_pct'] / 100], null, "A{$fila}");
            $h->getStyle("G{$fila}")->getNumberFormat()->setFormatCode('0.0%');
            $fila++;
        }
        $h->getColumnDimension('A')->setWidth(60);
        foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $h->getColumnDimension($col)->setWidth(16);
        }
    }

    private function diario(Worksheet $h, array $dias): void
    {
        $this->encabezado($h, 1, ['Fecha', 'Día', 'Origen', 'Registrado como', 'Asistencia', 'Inicio', 'Fin', 'Horas', 'Campo', 'Labor', 'Compañeros', 'Costo del día', 'Qué hizo']);
        $fila = 2;
        foreach ($dias as $d) {
            $fecha = Carbon::parse($d['fecha']);
            foreach ($d['entradas'] as $e) {
                $tramos = $e['tramos'] ?: [null];
                foreach ($tramos as $i => $t) {
                    $h->fromArray([
                        $fecha->format('d/m/Y'), ucfirst($fecha->translatedFormat('l')),
                        ucfirst($e['origen']) . ($e['grupo'] ? " {$e['grupo']}" : ''), $e['quien'], $e['asistencia_texto'],
                        $t['inicio'] ?? null, $t['fin'] ?? null, $t ? $t['horas'] : $e['horas'], $t['campo'] ?? null, $t['labor'] ?? null,
                        $t['companieros'] ?? null, $i === 0 ? $e['costo'] : null, $i === 0 ? $e['texto'] : null,
                    ], null, "A{$fila}");
                    if ($d['doble']) {
                        $h->getStyle("A{$fila}:M{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                    }
                    $fila++;
                }
            }
        }
        $h->setCellValue("A{$fila}", 'Total');
        $h->setCellValue("H{$fila}", $fila > 2 ? '=SUM(H2:H' . ($fila - 1) . ')' : 0);
        $h->setCellValue("L{$fila}", $fila > 2 ? '=SUM(L2:L' . ($fila - 1) . ')' : 0);
        $h->getStyle("A{$fila}:M{$fila}")->getFont()->setBold(true);
        $h->getStyle("L2:L{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (['A' => 11, 'B' => 11, 'C' => 16, 'D' => 30, 'E' => 14, 'F' => 8, 'G' => 8, 'H' => 8, 'I' => 10, 'J' => 34, 'K' => 12, 'L' => 13, 'M' => 110] as $col => $w) {
            $h->getColumnDimension($col)->setWidth($w);
        }
        $h->freezePane('A2');
        $h->setAutoFilter('A1:M' . max(1, $fila - 1));
    }

    private function produccion(Worksheet $h, array $dias): void
    {
        $this->encabezado($h, 1, ['Fecha', 'Origen', 'Registrado como', 'Campo', 'Labor', 'Recojos', 'Producción', 'Unidad', 'Estándar', '% del estándar', 'Resultado', 'Bono']);
        $fila = 2;
        foreach ($dias as $d) {
            foreach ($d['entradas'] as $e) {
                foreach ($e['producciones'] as $p) {
                    if (!$p['recojos']) {
                        continue;
                    }
                    $h->fromArray([
                        Carbon::parse($d['fecha'])->format('d/m/Y'), ucfirst($e['origen']), $e['quien'], $p['campo'], $p['labor'],
                        implode(' + ', $p['recojos']), $p['produccion'], $p['unidades'], $p['estandar'], null,
                        match ($p['estado']) { 'sobre' => 'Sobre el estándar', 'debajo' => 'Debajo del estándar', 'igual' => 'En el estándar', default => 'Sin estándar' },
                        $p['bono'],
                    ], null, "A{$fila}");
                    if ($p['estandar']) {
                        $h->setCellValue("J{$fila}", "=G{$fila}/I{$fila}");
                    }
                    $fila++;
                }
            }
        }
        $h->getStyle("J2:J{$fila}")->getNumberFormat()->setFormatCode('0.0%');
        foreach (['A' => 11, 'B' => 11, 'C' => 30, 'D' => 10, 'E' => 34, 'F' => 26, 'G' => 12, 'H' => 8, 'I' => 10, 'J' => 13, 'K' => 20, 'L' => 10] as $col => $w) {
            $h->getColumnDimension($col)->setWidth($w);
        }
        $h->freezePane('A2');
    }

    private function encabezado(Worksheet $h, int $fila, array $columnas): void
    {
        $h->fromArray($columnas, null, "A{$fila}");
        $rango = "A{$fila}:" . chr(64 + count($columnas)) . $fila;
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    }
}
