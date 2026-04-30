<?php

namespace App\Livewire\GestionReportes;

use App\Models\ReporteActividadDiario;
use App\Services\ActividadesResumenServicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Component;

class ReporteDiarioActividadesComponent extends Component
{
    public string $fecha;
    public string $agruparPor   = 'actividad';
    public array  $actividades   = [];
    public bool   $sinDatos      = false;
    public array $vistaAgrupada = [];

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

    public function cambiarAgrupacion(string $modo): void
    {
        $this->agruparPor = $modo;
        $this->cargarDatos();   // re-ordena desde la tabla estática, no la vista
    }

    public function actualizar(): void
    {
        app(ActividadesResumenServicio::class)->recalcularDia($this->fecha);
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
         $rows = app(ActividadesResumenServicio::class)
            ->cargarDia($this->fecha, $this->agruparPor);

        if ($rows->isEmpty()) {
            // Intento automático: si no hay datos en la tabla estática,
            // recalcula una sola vez desde la vista
            app(ActividadesResumenServicio::class)->recalcularDia($this->fecha);
            $rows = app(ActividadesResumenServicio::class)
                ->cargarDia($this->fecha, $this->agruparPor);
        }

        if ($rows->isEmpty()) {
            $this->sinDatos = true;
            $this->actividades = [];
            $this->totalActividades = $this->totalPlanilla
                = $this->totalCuadrilla = $this->totalMetodos = 0;
            return;
        }

        $this->sinDatos       = false;
        $totalPersonas        = $rows->sum(fn($r) => $r->total_planilla + $r->total_cuadrilla);

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
        $this->vistaAgrupada = $this->agruparActividades($this->actividades, $this->agruparPor);
    }
private function agruparActividades(array $filas, string $modo): array
{
    if ($modo === 'sin_agrupar') {
        return ['tipo' => 'plano', 'filas' => $filas];
    }

    if ($modo === 'actividad') {
        // Agrupar por codigo_labor + nombre_labor
        // Clave: codigo - descripcion → lista de campos
        $grupos = [];
        foreach ($filas as $fila) {
            $clave = $fila['codigo_labor'];
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'codigo_labor'    => $fila['codigo_labor'],
                    'nombre_labor'    => $fila['nombre_labor'],
                    'total_metodos'   => 0,
                    'total_planilla'  => 0,
                    'total_cuadrilla' => 0,
                    'total_personas'  => 0,
                    'campos'          => [],
                ];
            }
            $grupos[$clave]['total_metodos']   += $fila['total_metodos'];
            $grupos[$clave]['total_planilla']  += $fila['total_planilla'];
            $grupos[$clave]['total_cuadrilla'] += $fila['total_cuadrilla'];
            $grupos[$clave]['total_personas']  += $fila['total_personas'];
            $grupos[$clave]['campos'][]         = $fila['campo'];
        }

        // Ordenar por total_personas desc
        usort($grupos, fn($a, $b) => $b['total_personas'] <=> $a['total_personas']);

        return ['tipo' => 'por_actividad', 'grupos' => array_values($grupos)];
    }

    if ($modo === 'campo') {
        // Agrupar por campo → lista de actividades (cada una en su fila)
        $grupos = [];
        foreach ($filas as $fila) {
            $campo = $fila['campo'];
            if (!isset($grupos[$campo])) {
                $grupos[$campo] = [
                    'campo'       => $campo,
                    'actividades' => [],
                    'total_planilla'  => 0,
                    'total_cuadrilla' => 0,
                    'total_personas'  => 0,
                ];
            }
            $grupos[$campo]['actividades'][]       = $fila;
            $grupos[$campo]['total_planilla']      += $fila['total_planilla'];
            $grupos[$campo]['total_cuadrilla']     += $fila['total_cuadrilla'];
            $grupos[$campo]['total_personas']      += $fila['total_personas'];
        }

        // Ordenar grupos por total_personas desc
        usort($grupos, fn($a, $b) => $b['total_personas'] <=> $a['total_personas']);

        // Ordenar actividades dentro de cada grupo por total_personas desc
        foreach ($grupos as &$g) {
            usort($g['actividades'], fn($a, $b) => $b['total_personas'] <=> $a['total_personas']);
        }
        unset($g);

        return ['tipo' => 'por_campo', 'grupos' => array_values($grupos)];
    }

    return ['tipo' => 'plano', 'filas' => $filas];
}
    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
       $pdf = Pdf::loadView('reportes.actividades-diario-pdf', [
            'fechaFormateada'  => Carbon::parse($this->fecha)
                ->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'actividades'      => $this->actividades,
            'totalActividades' => $this->totalActividades,
            'totalPlanilla'    => $this->totalPlanilla,
            'totalCuadrilla'   => $this->totalCuadrilla,
            'totalMetodos'     => $this->totalMetodos,
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