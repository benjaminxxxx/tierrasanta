<?php

namespace App\Livewire\GestionReportes;

use App\Services\AsistenciasResumenServicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Component;

class ReporteAnualAsistenciasComponent extends Component
{
    public int  $anio;
    public bool $sinDatos    = false;
    public bool $calculando  = false;

    // Resumen global del año
    public int   $totalBaseAnio  = 0;
    public int   $asistidosAnio  = 0;
    public int   $ausentesAnio   = 0;
    public int   $diasHabilesAnio = 0;
    public float $pctAsistAnio   = 0;

    // Detalle por mes: array de 12 entradas (null si no hay datos)
    public array $meses = [];

    // Totales por código agregados en el año (para el gráfico doughnut)
    public array $totalesAnio = [];

    public function mount(int $anio): void
    {
        $this->anio = $anio;
        $this->cargarDatos();
    }

    // ── Recalcula todos los meses del año desde PlanResumenDiario ────
    public function actualizar(): void
    {
        $this->calculando = true;
        
        $svc = app(AsistenciasResumenServicio::class);

        for ($m = 1; $m <= 12; $m++) {
            // Solo recalcula meses que ya pasaron o es el mes actual
            $limite = Carbon::createFromDate($this->anio, $m, 1)->endOfMonth();
            if ($limite->isFuture()) continue;

            $svc->recalcularMes($m, $this->anio);
        }

        $this->calculando = false;
        $this->cargarDatos();
        $this->dispatch('resumenAnualActualizado', totalesAnio: $this->totalesAnio);
    }

    private function cargarDatos(): void
    {
        $svc = app(AsistenciasResumenServicio::class);

        $this->meses        = [];
        $this->totalesAnio  = [];
        $acumulador         = [];   // [codigo => datos + total anual]
        $totalBaseAnio      = 0;
        $asistidosAnio      = 0;
        $diasHabilesAnio    = 0;

        $nombresMeses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',    4 => 'Abril',
            5 => 'Mayo',  6 => 'Junio',   7 => 'Julio',    8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        for ($m = 1; $m <= 12; $m++) {
            $datos = $svc->cargarMes($m, $this->anio);

            if (!$datos) {
                $this->meses[$m] = [
                    'mes'        => $m,
                    'nombre'     => $nombresMeses[$m],
                    'sinDatos'   => true,
                    'totalBase'  => 0,
                    'diasHabiles'=> 0,
                    'empleados'  => 0,
                    'asistidos'  => 0,
                    'ausentes'   => 0,
                    'pctAsist'   => 0,
                    'totales'    => [],
                ];
                continue;
            }

            $asistidosMes = collect($datos['totales'])->where('acumula', 1)->sum('total');
            $ausentesMes  = $datos['totalBase'] - $asistidosMes;
            $pctMes       = $datos['totalBase'] > 0
                ? round(($asistidosMes / $datos['totalBase']) * 100, 1)
                : 0;

            $this->meses[$m] = [
                'mes'         => $m,
                'nombre'      => $nombresMeses[$m],
                'sinDatos'    => false,
                'totalBase'   => $datos['totalBase'],
                'diasHabiles' => $datos['diasHabiles'],
                'empleados'   => $datos['empleados'],
                'asistidos'   => $asistidosMes,
                'ausentes'    => $ausentesMes,
                'pctAsist'    => $pctMes,
                'totales'     => $datos['totales'],
            ];

            // Acumular anuales
            $totalBaseAnio   += $datos['totalBase'];
            $asistidosAnio   += $asistidosMes;
            $diasHabilesAnio += $datos['diasHabiles'];

            // Agregar por código para el gráfico anual
            foreach ($datos['totales'] as $t) {
                $cod = $t['codigo'];
                if (!isset($acumulador[$cod])) {
                    $acumulador[$cod] = [
                        'codigo'      => $cod,
                        'descripcion' => $t['descripcion'],
                        'color'       => $t['color'],
                        'tipo'        => $t['tipo'],
                        'acumula'     => $t['acumula'],
                        'total'       => 0,
                    ];
                }
                $acumulador[$cod]['total'] += $t['total'];
            }
        }

        $this->totalBaseAnio   = $totalBaseAnio;
        $this->asistidosAnio   = $asistidosAnio;
        $this->ausentesAnio    = $totalBaseAnio - $asistidosAnio;
        $this->diasHabilesAnio = $diasHabilesAnio;
        $this->pctAsistAnio    = $totalBaseAnio > 0
            ? round(($asistidosAnio / $totalBaseAnio) * 100, 1)
            : 0;

        $this->sinDatos = $totalBaseAnio === 0;

        // Calcular porcentaje anual por código
        $this->totalesAnio = collect($acumulador)
            ->map(fn($t) => array_merge($t, [
                'porcentaje' => $totalBaseAnio > 0
                    ? round(($t['total'] / $totalBaseAnio) * 100, 1)
                    : 0,
            ]))
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    public function exportarPdf(string $chartBase64 = ''): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = Pdf::loadView('reportes.asistencias-anual-pdf', [
            'anio'           => $this->anio,
            'totalBaseAnio'  => $this->totalBaseAnio,
            'asistidosAnio'  => $this->asistidosAnio,
            'ausentesAnio'   => $this->ausentesAnio,
            'diasHabilesAnio'=> $this->diasHabilesAnio,
            'pctAsistAnio'   => $this->pctAsistAnio,
            'meses'          => $this->meses,
            'totalesAnio'    => $this->totalesAnio,
            'chartBase64'    => $chartBase64 ?: null,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "asistencias_anual_{$this->anio}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.gestion-reportes.reporte-anual-asistencias-component');
    }
}