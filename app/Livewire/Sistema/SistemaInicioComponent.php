<?php

namespace App\Livewire\Sistema;

use App\Constants\Permisos;
use App\Services\Sistema\Inicio\SistemaInicioConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Inicio (dashboard): indicadores del mes, cosecha y mano de obra del año, alertas (tareas pendientes) y accesos
 * a los módulos del flujo diario. Cada bloque se muestra solo con el permiso de su módulo. Diseño: DESIGN.md.
 */
#[Title('Inicio')]
class SistemaInicioComponent extends Component
{
    public int $anio;
    public int $mes;

    /** Accesos del flujo diario: [título, descripción, ícono, ruta, permiso] */
    private const ACCESOS = [
        ['Actividades diarias', 'Planilla: labores del día', 'fa-clipboard-list', 'planilla.registro_diario', Permisos::PLANILLA_ACTIVIDAD],
        ['Asistencia mensual', 'Planilla: asistencia y suspensiones', 'fa-calendar-check', 'planilla.asistencia', Permisos::PLANILLA_ASISTENCIA],
        ['Cuadrilla del día', 'Actividades y horas de cuadrilla', 'fa-people-group', 'cuadrilla.registro_diario', Permisos::CUADRILLA_DIARIO],
        ['Riego', 'Reporte diario de regadores', 'fa-droplet', 'riego.reporte_diario', Permisos::CAMPO_RIEGO_REPORTE],
        ['Ingreso de cochinilla', 'Cosecha por lote', 'fa-weight-scale', 'cochinilla.ingreso', Permisos::COCHINILLA_INGRESO],
        ['Infestación', 'Infestaciones y reinfestaciones', 'fa-seedling', 'cochinilla.infestacion', Permisos::COCHINILLA_INFESTACION],
        ['Compras', 'Entradas de almacén', 'fa-cart-shopping', 'almacen.compras', Permisos::INSUMO_COMPRA],
        ['Salidas de almacén', 'Pesticidas y fertilizantes', 'fa-box-open', 'almacen.salida_productos', Permisos::INSUMO_SALIDA],
        ['Campañas', 'Campañas por campo', 'fa-layer-group', 'campania.por_campo', Permisos::CAMPAÑA_POR_CAMPO],
        ['Costos mensuales', 'Cierre de costos', 'fa-chart-pie', 'costos.mensuales', Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA],
        ['Caja', 'Movimientos de caja', 'fa-cash-register', 'caja.movimientos', Permisos::CAJA_MOVIMIENTO],
        ['Excel de recursos', 'Campos, labores, usuarios…', 'fa-file-excel', 'sistema.configuracion', Permisos::SISTEMA_CONFIGURACION],
    ];

    public function mount(): void
    {
        $this->anio = now()->year;
        $this->mes = now()->month;
    }

    public function mesAnterior(): void
    {
        $this->moverMes(-1);
    }

    public function mesSiguiente(): void
    {
        $this->moverMes(1);
    }

    private function moverMes(int $delta): void
    {
        $f = Carbon::create($this->anio, $this->mes, 1)->addMonths($delta);
        if ($f->greaterThan(now()->startOfMonth())) {
            return;
        }
        [$this->anio, $this->mes] = [$f->year, $f->month];
    }

    public function actualizar(SistemaInicioConsulta $consulta): void
    {
        $consulta->olvidar($this->anio, $this->mes);
    }

    public function abrirTareas(): void
    {
        $this->dispatch('abrirTareasPendientes', mes: sprintf('%04d-%02d', $this->anio, $this->mes));
    }

    public function render(SistemaInicioConsulta $consulta)
    {
        $puede = fn(string ...$permisos) => collect($permisos)->contains(fn($p) => Gate::allows($p));
        return view('livewire.sistema.sistema-inicio-component', [
            'datos' => $consulta->resumen($this->anio, $this->mes),
            'alertas' => $consulta->alertas($this->anio, $this->mes),
            'accesos' => array_values(array_filter(self::ACCESOS, fn($a) => Route::has($a[3]) && Gate::allows($a[4]))),
            'ver' => [
                'cochinilla' => $puede(Permisos::COCHINILLA, Permisos::COCHINILLA_INGRESO),
                'costos' => $puede(Permisos::CAMPO_MANO_OBRA, Permisos::CONTABILIDAD, Permisos::CONTABILIDAD_COSTO_MENSUAL_LISTA),
                'compras' => $puede(Permisos::INSUMO, Permisos::INSUMO_COMPRA),
                'personal' => $puede(Permisos::PLANILLA, Permisos::CUADRILLA),
                'campanias' => $puede(Permisos::CAMPAÑA, Permisos::CAMPAÑA_POR_CAMPO),
            ],
            'periodo' => Carbon::create($this->anio, $this->mes, 1)->locale('es')->isoFormat('MMMM [de] YYYY'),
            'esMesActual' => $this->anio === now()->year && $this->mes === now()->month,
        ]);
    }
}
