<?php

namespace App\Livewire\GestionReportes;

use App\Models\ReporteActividadDiario;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Component;

class ReporteDiarioActividadesComponent extends Component
{
    public string $fecha;
    public array  $actividades   = [];
    public bool   $sinDatos      = false;

    // Métricas rápidas calculadas en PHP para no repetir en blade
    public int    $totalActividades  = 0;
    public int    $totalPlanilla     = 0;   // suma de distintos, ver nota
    public int    $totalCuadrilla    = 0;
    public int    $totalMetodos      = 0;

    public function mount(string $fecha): void
    {
        $this->fecha = $fecha;
        $this->cargarDatos();
    }

    public function actualizar(): void
    {
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
        $rows = ReporteActividadDiario::porFecha($this->fecha);

        if ($rows->isEmpty()) {
            $this->sinDatos     = true;
            $this->actividades  = [];
            $this->totalActividades = $this->totalPlanilla = $this->totalCuadrilla = $this->totalMetodos = 0;
            return;
        }

        $this->sinDatos = false;

        // Total combinado (planilla + cuadrilla) para calcular porcentaje de participación
        $totalPersonas = $rows->sum(fn($r) => $r->total_planilla + $r->total_cuadrilla);

        $this->actividades = $rows->map(function ($r) use ($totalPersonas) {
            $personas = $r->total_planilla + $r->total_cuadrilla;
            return [
                'actividad_id'    => $r->actividad_id,
                'campo'           => $r->campo,
                'codigo_labor'    => $r->codigo_labor,
                'nombre_labor'    => $r->nombre_labor,
                'unidades'        => $r->unidades,
                'recojos'         => $r->recojos,
                'total_metodos'   => $r->total_metodos,
                'total_planilla'  => $r->total_planilla,
                'total_cuadrilla' => $r->total_cuadrilla,
                'total_personas'  => $personas,
                // % sobre el total de personas que trabajaron ese día
                'pct_planilla'    => $totalPersonas > 0
                    ? round(($r->total_planilla  / $totalPersonas) * 100, 1) : 0,
                'pct_cuadrilla'   => $totalPersonas > 0
                    ? round(($r->total_cuadrilla / $totalPersonas) * 100, 1) : 0,
            ];
        })->toArray();

        $this->totalActividades = $rows->count();
        $this->totalPlanilla    = $rows->sum('total_planilla');
        $this->totalCuadrilla   = $rows->sum('total_cuadrilla');
        $this->totalMetodos     = $rows->sum('total_metodos');
    }

    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = Pdf::loadView('reportes.actividades-diario-pdf', [
            'fechaFormateada' => Carbon::parse($this->fecha)
                ->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'actividades'     => $this->actividades,
            'totalActividades'=> $this->totalActividades,
            'totalPlanilla'   => $this->totalPlanilla,
            'totalCuadrilla'  => $this->totalCuadrilla,
            'totalMetodos'    => $this->totalMetodos,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "actividades_{$this->fecha}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.gestion-reportes.reporte-diario-actividades-component');
    }
}