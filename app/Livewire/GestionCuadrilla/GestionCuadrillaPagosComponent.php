<?php

namespace App\Livewire\GestionCuadrilla;

use App\Exports\Cuadrilla\InformeGeneralCuadrilla;
use App\Models\CuadRegistroDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Support\DateHelper;
use App\Support\ExcelHelper;
use App\Traits\ListasComunes\ConGrupoCuadrilla;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GestionCuadrillaPagosComponent extends Component
{
    use LivewireAlert, ConGrupoCuadrilla;
    public $fecha_inicio;
    public $fecha_fin;
    public $grupoSeleccionado;
    public $nombre_cuadrillero;
    public $header;
    public $registros = [];
    public function mount()
    {
        $this->grupoSeleccionado = Session::get('grupo_seleccionado');
        $this->fecha_inicio = Session::get('fecha_inicio', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $this->fecha_fin = Session::get('fecha_fin', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $this->buscarRegistros();
    }
    public function updatedGrupoSeleccionado($valor)
    {
        Session::put('grupo_seleccionado', $valor);
        $this->buscarRegistros();
    }
    public function updatedFechaInicio($valor)
    {
        Session::put('fecha_inicio', $valor);
        $this->buscarRegistros();
    }
    public function updatedFechaFin($valor)
    {
        Session::put('fecha_fin', $valor);
        $this->buscarRegistros();
    }
    public function buscarRegistros()
    {
        $query = CuadRegistroDiario::query();
        if ($this->grupoSeleccionado) {
            $query->where('codigo_grupo', $this->grupoSeleccionado);
        }
        if ($this->fecha_inicio) {
            $query->whereDate('fecha', '>=', $this->fecha_inicio);
        }
        if ($this->fecha_fin) {
            $query->whereDate('fecha', '<=', $this->fecha_fin);
        }
        if ($this->nombre_cuadrillero) {
            $query->whereHas('cuadrillero', function ($q) {
                $q->where('nombres', 'like', '%' . $this->nombre_cuadrillero . '%');
            });
        }

        $this->registros = $query->orderBy('fecha')
            ->orderBy('codigo_grupo')
            ->orderBy('cuadrillero_id')
            ->with('detalleHoras')
            ->get()
            ->map(function ($registroDiario) {
                return [
                    'fecha' => formatear_fecha($registroDiario->fecha),
                    'codigo_grupo' => $registroDiario->codigo_grupo,
                    'nombres' => $registroDiario->cuadrillero->nombres,
                    'costo_personalizado_dia' => $registroDiario->costo_personalizado_dia,
                    'total_bono' => $registroDiario->total_bono,
                    'costo_dia' => $registroDiario->costo_dia,
                    'total_horas' => $registroDiario->total_horas,
                    'esta_pagado' => $registroDiario->esta_pagado,
                    'bono_esta_pagado' => $registroDiario->bono_esta_pagado,
                    'detalle_campos' => $registroDiario->detalleHoras
                        ->pluck('campo_nombre')
                        ->implode(', '),
                    'horas_detalladas' => $registroDiario->detalleHoras
                        ->sum(function ($detalle) {
                            $inicio = Carbon::createFromFormat('H:i:s', $detalle->hora_inicio);
                            $fin = Carbon::createFromFormat('H:i:s', $detalle->hora_fin);
                            return $inicio->diffInMinutes($fin) / 60;
                        })
                ];
            })
            ->toArray();
    }
    public function generarInformeGeneralCuadrilla()
    {
        try {
            $data = [
                'registros' => $this->registros,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'codigo_grupo' => $this->grupoSeleccionado,
                'nombre_cuadrillero' => $this->nombre_cuadrillero,
            ];

            $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_cuadrilla_informe_general.xlsx');

            $hoja = $spreadsheet->getSheetByName('INFORME GENERAL CUADRILLA');

            if (!$hoja) {
                throw new Exception("La plantilla no contiene la hoja 'INFORME GENERAL CUADRILLA'.");
            }

            // 2️⃣ Leer tabla INFORME_GENERAL
            $table = $hoja->getTableByName('INFORME_GENERAL');

            if (!$table) {
                throw new Exception("La plantilla no tiene una tabla llamada INFORME_GENERAL.");
            }

            $hoja->setCellValue("C2", $data['fecha_inicio']);
            $hoja->setCellValue("C3", $data['fecha_fin']);
            $hoja->setCellValue("C4", $data['codigo_grupo']);
            $hoja->setCellValue("C5", $data['nombre_cuadrillero']);

            // 3️⃣ Primera fila debajo de los headers
            $filaInicial = ExcelHelper::primeraFila($table);
            $fila = $filaInicial + 1;
            $contador = 1;

            foreach ($data['registros'] as $reg) {

                // FECHA EXCEL (si viene string dd/mm/yyyy)
                $fechaExcel = ExcelDate::PHPToExcel($reg['fecha']);

                $hoja->setCellValue("A{$fila}", $contador);
                $hoja->setCellValue("B{$fila}", $fechaExcel);
                $hoja->setCellValue("C{$fila}", $reg['codigo_grupo']);
                $hoja->setCellValue("D{$fila}", $reg['nombres']);
                $hoja->setCellValue("E{$fila}", $reg['costo_personalizado_dia']);
                $hoja->setCellValue("F{$fila}", $reg['total_horas']); // Horas Registradas
                $hoja->setCellValue("G{$fila}", $reg['horas_detalladas']);
                $hoja->setCellValue("H{$fila}", $reg['costo_dia']);
                $hoja->setCellValue("I{$fila}", $reg['total_bono']);
                $hoja->setCellValue("J{$fila}", '=H' . $fila . '+I' . $fila);
                $hoja->setCellValue("K{$fila}", $reg['esta_pagado'] ? 'Sí' : 'No');
                $hoja->setCellValue("L{$fila}", $reg['bono_esta_pagado'] ? 'Sí' : 'No');
                $hoja->setCellValue("M{$fila}", $reg['detalle_campos']); // concatenado por comas

                // FORMATO FECHA
                $hoja->getStyle("B{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode('DD/MM/YYYY');

                $contador++;
                $fila++;
            }
            // 4️⃣ Actualizar tamaño de tabla
            ExcelHelper::actualizarRangoTabla($table, $fila - 1);

            // 5️⃣ Fila de totales
            $filaTotales = $fila; // fila final después de los registros

            $hoja->setCellValue("D{$filaTotales}", "TOTALES:");
            $hoja->getStyle("D{$filaTotales}")->getFont()->setBold(true);

            // SUMATORIAS
            $hoja->setCellValue("E{$filaTotales}", "=SUM(E" . ($filaInicial + 1) . ":E" . ($fila - 1) . ")");
            $hoja->setCellValue("F{$filaTotales}", "=SUM(F" . ($filaInicial + 1) . ":F" . ($fila - 1) . ")");
            $hoja->setCellValue("G{$filaTotales}", "=SUM(G" . ($filaInicial + 1) . ":G" . ($fila - 1) . ")");
            $hoja->setCellValue("H{$filaTotales}", "=SUM(H" . ($filaInicial + 1) . ":H" . ($fila - 1) . ")");
            $hoja->setCellValue("I{$filaTotales}", "=SUM(I" . ($filaInicial + 1) . ":I" . ($fila - 1) . ")");
            $hoja->setCellValue("J{$filaTotales}", "=SUM(J" . ($filaInicial + 1) . ":J" . ($fila - 1) . ")");

            // Negrita a toda la fila de totales
            $hoja->getStyle("A{$filaTotales}:M{$filaTotales}")
                ->getFont()->setBold(true);

            return ExcelHelper::descargar($spreadsheet, 'informe_general_cuadrilla.xlsx');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registrarPago($listaPagos)
    {
        try {
            CuadrilleroServicio::registrarPagos($listaPagos, $this->fecha_inicio, $this->fecha_fin);
            $this->alert('success', 'Registros con pagos procesados exitosamente.');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-pagos-component');
    }
}
