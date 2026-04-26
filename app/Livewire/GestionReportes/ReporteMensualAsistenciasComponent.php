<?php

namespace App\Livewire\GestionReportes;

use App\Models\ParametroMensual;
use App\Models\PlanResumenDiario;
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

    // Prefijo de clave en parametros_mensuales
    const CLAVE_PREFIX = 'asistencias_resumen_';
    const CLAVE_TOTAL = 'asistencias_total_planilla';

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
        $this->recalcularYPersistir();
        $this->cargarDatos();
        $this->dispatch('resumenActualizado', totales: $this->totales);
    }

    private function recalcularYPersistir(): void
    {
        // 1. Traer todos los resúmenes diarios del mes/año
        $resumenes = PlanResumenDiario::with('totales')
            ->whereYear('fecha', $this->anio)
            ->whereMonth('fecha', $this->mes)
            ->where('total_planilla', '>', 0)  // solo días con registro real
            ->get();

        if ($resumenes->isEmpty()) {
            // Limpiar parámetros previos y marcar sin datos
            $this->limpiarParametros();
            return;
        }

        // Días hábiles = cantidad de resúmenes con planilla > 0
        $diasHabiles = $resumenes->count();
        // Empleados = promedio del total_planilla entre esos días
        // (por si hay alguna variación puntual, el promedio es más robusto que max)
        $empleados = (int) round($resumenes->avg('total_planilla'));
        // Base real: días hábiles × empleados
        $totalBase = $diasHabiles * $empleados;

        $agregado = [];
        foreach ($resumenes as $resumen) {
            foreach ($resumen->totales as $t) {
                $cod = $t->codigo;
                if (!isset($agregado[$cod])) {
                    $agregado[$cod] = [
                        'codigo' => $cod,
                        'descripcion' => $t->descripcion,
                        'color' => $t->color,
                        'tipo' => $t->tipo,
                        'acumula' => $t->acumula_asistencia,
                        'afecta_sueldo' => $t->afecta_sueldo,
                        'total' => 0,
                    ];
                }
                $agregado[$cod]['total'] += $t->total_asistidos;
            }
        }

        $this->limpiarParametros();

        // Guardar los tres valores que necesita la vista
        ParametroMensual::establecer(
            $this->mes,
            $this->anio,
            self::CLAVE_TOTAL,
            valor: $totalBase,
            observacion: "Base: {$diasHabiles} días × {$empleados} empleados"
        );

        ParametroMensual::establecer(
            $this->mes,
            $this->anio,
            'asistencias_dias_habiles',
            valor: $diasHabiles
        );

        ParametroMensual::establecer(
            $this->mes,
            $this->anio,
            'asistencias_empleados',
            valor: $empleados
        );

        foreach ($agregado as $cod => $datos) {
            ParametroMensual::updateOrCreate(
                ['mes' => $this->mes, 'anio' => $this->anio, 'clave' => self::CLAVE_PREFIX . $cod],
                [
                    'valor_texto' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                    'observacion' => 'Resumen mensual de asistencias'
                ]
            );
        }
    }

    private function limpiarParametros(): void
    {
        ParametroMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->where(function ($q) {
                $q->where('clave', self::CLAVE_TOTAL)
                    ->orWhere('clave', 'like', self::CLAVE_PREFIX . '%');
            })
            ->delete();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Carga desde parametros_mensuales (fuente de verdad para el render)
    // ──────────────────────────────────────────────────────────────────────────
    private function cargarDatos(): void
    {

        $this->totalPlanilla = (int) ParametroMensual::obtener(self::CLAVE_TOTAL, $this->mes, $this->anio, 0);
        $this->diasHabiles = (int) ParametroMensual::obtener('asistencias_dias_habiles', $this->mes, $this->anio, 0);
        $this->empleados = (int) ParametroMensual::obtener('asistencias_empleados', $this->mes, $this->anio, 0);


        $params = ParametroMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->where('clave', 'like', self::CLAVE_PREFIX . '%')
            ->get();

        if ($params->isEmpty() || $this->totalPlanilla === 0) {
            $this->sinDatos = true;
            $this->totales = [];
            return;
        }

        $this->sinDatos = false;

        $this->totales = $params
            ->map(function ($param) {
                $datos = json_decode($param->valor_texto, true);
                if (!$datos)
                    return null;

                $porcentaje = $this->totalPlanilla > 0
                    ? round(($datos['total'] / $this->totalPlanilla) * 100, 1)
                    : 0;

                return array_merge($datos, ['porcentaje' => $porcentaje]);
            })
            ->filter()
            ->sortByDesc('total')
            ->values()
            ->toArray();
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