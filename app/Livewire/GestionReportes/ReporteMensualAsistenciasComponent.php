<?php

namespace App\Livewire\GestionReportes;

use App\Models\ParametroMensual;
use App\Models\PlanResumenDiario;
use App\Services\AsistenciasResumenServicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Component;

class ReporteMensualAsistenciasComponent extends Component
{
    public int $mes;
    public int $anio;

    public array $totales = [];
    public int $totalPlanilla = 0;
    public bool $sinDatos = false;
    public int $diasHabiles = 0;
    public int $empleados = 0;

    public function mount(int $mes, int $anio): void
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->cargarDatos();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Botón "Actualizar": recalcula desde PlanResumenDiario y persiste
    // ──────────────────────────────────────────────────────────────────────────
    public function actualizar(): void
    {
        app(AsistenciasResumenServicio::class)->recalcularMes($this->mes, $this->anio);
        $this->cargarDatos();
        $this->dispatch('resumenActualizado', totales: $this->totales);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Carga desde parametros_mensuales (fuente de verdad para el render)
    // ──────────────────────────────────────────────────────────────────────────
    private function cargarDatos(): void
    {
        $datos = app(AsistenciasResumenServicio::class)->cargarMes($this->mes, $this->anio);

        if (!$datos) {
            $this->sinDatos      = true;
            $this->totales       = [];
            $this->totalPlanilla = 0;
            $this->diasHabiles   = 0;
            $this->empleados     = 0;
            return;
        }

        $this->sinDatos      = false;
        $this->totalPlanilla = $datos['totalBase'];
        $this->diasHabiles   = $datos['diasHabiles'];
        $this->empleados     = $datos['empleados'];
        $this->totales       = $datos['totales'];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Export PDF
    // ──────────────────────────────────────────────────────────────────────────
    public function exportarPdf(string $chartBase64 = ''): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $asistidos = collect($this->totales)->where('acumula', 1)->sum('total');
        $ausentes = $this->totalPlanilla - $asistidos;

        $mesNombre = Carbon::createFromDate($this->anio, $this->mes, 1)
            ->isoFormat('MMMM [de] YYYY');

        $pdf = Pdf::loadView('reportes.asistencias-mensual-pdf', [
            'periodoFormateado' => ucfirst($mesNombre),
            'totalPlanilla' => $this->totalPlanilla,
            'diasHabiles' => $this->diasHabiles,
            'empleados' => $this->empleados,
            'asistidos' => $asistidos,
            'ausentes' => $ausentes,
            'pctAsist' => $this->totalPlanilla > 0
                ? round(($asistidos / $this->totalPlanilla) * 100, 1) : 0,
            'pctAusent' => $this->totalPlanilla > 0
                ? round(($ausentes / $this->totalPlanilla) * 100, 1) : 0,
            'totales' => $this->totales,
            'chartBase64' => $chartBase64 ?: null,
        ])->setPaper('a4', 'landscape');

        $filename = "asistencias_{$this->mes}_{$this->anio}.pdf";

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.gestion-reportes.reporte-mensual-asistencias-component');
    }
}