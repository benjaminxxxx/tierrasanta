<?php

namespace App\Exports\Cuadrilla;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CuadrillaRptPagoConsolidadoExportCopy implements FromArray, WithEvents, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return "Consolidado";
    }

    public function array(): array
    {
        $rows = [];

        // Fila 1: Fecha (alineada con hoja Pagos)
        $rows[] = [formatear_fecha($this->data['fecha_reporte'])];

        // Fila 2: Título consolidado
        $rows[] = [$this->data['titulo_consolidado']];

        // Fila 3 y 4: Espacio
        $rows[] = []; //Aqui en el reporte pagos va el numero de semana
        $rows[] = [];

        // Fila 5: Header principal (para alinear con la fila 4 de Pagos)
        $header = ['N°', 'NOMBRES'];

        // Agregar columnas de semanas dinámicamente
        foreach ($this->data['tramos'] as $index => $tramo) {
            $header[] = 'SEMANA ' . ($index + 1);
        }

        $header[] = 'BONO';
        $header[] = 'TOTAL';
        $header[] = 'FIRMA';

        $rows[] = $header;
        $rows[] = [];// en la hoja Pagos, hay dos filas, aqui vamos a hacer un merged, por eso no debe haber data

        // Obtener todos los trabajadores únicos
        $trabajadores = $this->obtenerTrabajadoresUnicos();

        // Fila 5 en adelante: Data de trabajadores (alineada con hoja Pagos)
        $numeroTrabajador = 1;
        foreach ($trabajadores as $nombreTrabajador) {
            $row = [$numeroTrabajador, $nombreTrabajador];

            $totalGeneral = 0;

            // Calcular totales por semana para este trabajador
            foreach ($this->data['tramos'] as $tramo) {
                $filaInicial = $this->calcularFilaInicialTramo($index);
                $filaTotal = $filaInicial + count($tramo['pagos']) + 3; 
                $celdaReferencia = "Pagos!I{$filaTotal}";
                $row[] = "={$celdaReferencia}";
            }

            // Buscar bono para este trabajador
            $bono = 0;
            foreach ($this->data['bonos'] as $bonoData) {
                if ($bonoData['nombre'] === $nombreTrabajador) {
                    $bono = $bonoData['bono'];
                    break;
                }
            }

            $row[] = $bono; // TOTAL BONO
            $row[] = $totalGeneral + $bono; // TOTAL A PAGAR
            $row[] = ''; // S/.
            $row[] = ''; // FIRMA

            $rows[] = $row;
            $numeroTrabajador++;
        }
        /*
        // Calcular la fila donde debe empezar el resumen de bonos
        */

        return $rows;
    }
    protected function calcularFilaInicialTramo($index)
    {
        $filaBase = 6; // la fila donde empieza el primer tramo en Pagos
        $offset = 0;

        for ($i = 0; $i < $index; $i++) {
            $offset += count($this->data['tramos'][$i]['pagos']) + 7;
            // 6 = filas de encabezado + total + espacios
        }

        return $filaBase + $offset;
    }
    private function obtenerTrabajadoresUnicos(): array
    {
        $trabajadores = [];

        // Obtener trabajadores de todos los tramos
        foreach ($this->data['tramos'] as $tramo) {
            foreach ($tramo['pagos'] as $pago) {
                if (!in_array($pago['nombre'], $trabajadores)) {
                    $trabajadores[] = $pago['nombre'];
                }
            }
        }

        return $trabajadores;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

               /* 

                // Encontrar la fila del resumen de bonos
                $filaResumenBonos = 24; // Fija en fila 24 como en la imagen
    
               

                
               

                */
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}