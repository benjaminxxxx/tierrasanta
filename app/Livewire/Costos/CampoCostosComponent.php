<?php

namespace App\Livewire\Costos;

use App\Models\Campania;
use App\Models\CampoCampania;
use App\Models\PlanMensualPersonal;
use App\Models\ResumenCostoDiario;
use App\Services\Costos\Consolidacion\ConsolidarCostoGastosGeneralesServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoInsumosServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoMaquinariaServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoServiciosCampoServicio;
use App\Services\Campania\CampaniaServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CampoCostosComponent extends Component
{
    use LivewireAlert, WithPagination, HandlesAlerts;

    public $campanias = [];
    public $campaniaId = null;
    public $fechaInicio = null;
    public $fechaFin = null;

    // [origen_tipo => etiqueta]
    public $tiposDisponibles = [];
    public $tiposSeleccionados = [];
    public $filtroCampo = null;
    public $campaniasDelCampo = [];
    public $filtro = '';
    public $reporteFileCampania = null;
    public function mount()
    {
        $this->campanias = CampoCampania::orderByDesc('fecha_inicio')->get();
        $this->tiposDisponibles = ResumenCostoDiario::tiposOrigenDisponibles();
        $this->tiposSeleccionados = array_keys($this->tiposDisponibles); // todos visibles por defecto
    }

    public function marcarTodosLosTipos(): void
    {
        $this->tiposSeleccionados = array_keys($this->tiposDisponibles);
        $this->resetPage();
    }

    public function desmarcarTodosLosTipos(): void
    {
        $this->tiposSeleccionados = [];
        $this->resetPage();
    }

    /**
     * Quita todos los filtros de una sola vez (campo, campaña, fechas, texto y tipos).
     */
    public function limpiarFiltros(): void
    {
        $this->reset(['filtroCampo', 'campaniaId', 'fechaInicio', 'fechaFin', 'filtro', 'reporteFileCampania']);
        $this->campaniasDelCampo = collect();
        $this->marcarTodosLosTipos();
    }

    public function updatedFiltroCampo($valor)
    {
        $this->campaniaId = null;
        $this->fechaInicio = null;
        $this->fechaFin = null;
        $this->reporteFileCampania = null;

        $this->cargarCampaniasPorCampo($valor);

        $this->resetPage();
    }
    /**
     * Carga o refresca la colección de campañas asociadas al campo seleccionado.
     */
    private function cargarCampaniasPorCampo($campo): void
    {
        $this->campaniasDelCampo = $campo
            ? CampoCampania::where('campo', $campo)->orderByDesc('fecha_inicio')->get()
            : collect();
    }
    /**
     * Busca los datos de la campaña seleccionada y asigna las fechas y el archivo de reporte.
     *
     * @param mixed $campaniaId
     * @return void
     */
    public function buscarReporte($campaniaId): void
    {
        if ($campaniaId) {
            // Consultamos directamente el registro fresco en la BD para evitar datos en caché
            $campania = CampoCampania::find((int) $campaniaId);

            if ($campania) {
                $this->fechaInicio = $campania->fecha_inicio instanceof \DateTimeInterface
                    ? $campania->fecha_inicio->format('Y-m-d')
                    : ($campania->fecha_inicio ? date('Y-m-d', strtotime($campania->fecha_inicio)) : null);

                $this->fechaFin = $campania->fecha_fin instanceof \DateTimeInterface
                    ? $campania->fecha_fin->format('Y-m-d')
                    : ($campania->fecha_fin ? date('Y-m-d', strtotime($campania->fecha_fin)) : null);

                $this->reporteFileCampania = $campania->gasto_resumen_bdd_file ?? null;
                return;
            }
        }

        $this->fechaInicio = null;
        $this->fechaFin = null;
        $this->reporteFileCampania = null;
    }

    public function updatedCampaniaId($valor)
    {
        $this->buscarReporte($valor);
        $this->resetPage();
    }

    public function consolidarCostoCampos()
    {
        try {
            if (!$this->campaniaId) {
                throw new \Exception("Selecciona una campaña antes de consolidar.");
            }

            $campania = CampoCampania::findOrFail($this->campaniaId);
            $totalPlanilla = app(ConsolidarCostoManoObraServicio::class)->consolidarPlanilla($campania);
            $totalGastosGenerales = app(ConsolidarCostoGastosGeneralesServicio::class)->consolidarGastosGenerales($campania);
            $totalMaquinaria = app(ConsolidarCostoMaquinariaServicio::class)->consolidarMaquinaria($campania);
            $totalInsumos = app(ConsolidarCostoInsumosServicio::class)->consolidarInsumos($campania);
            $totalServicios = app(ConsolidarCostoServiciosCampoServicio::class)->consolidarServicios($campania);

            // 1. Generar la BDD Mensual y actualizar la ruta del reporte en la BD
            app(CampaniaServicio::class)->generarBddMensual($campania->id);

            // 2. Refrescar la colección en memoria con los datos recién guardados
            $this->cargarCampaniasPorCampo($this->filtroCampo);

            // 3. Actualizar los estados locales (fechaInicio, fechaFin, reporteFileCampania)
            $this->buscarReporte($this->campaniaId);

            $this->alert('success', "Consolidado: {$totalPlanilla} planilla, {$totalGastosGenerales} gastos generales, {$totalMaquinaria} maquinaria, {$totalServicios} servicios en campo.");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function updatedTiposSeleccionados()
    {
        $this->resetPage();
    }


    public function aplicarFiltro()
    {
        $this->resetPage();
    }
   
    public function render()
    {
        $resumenes = $this->consultaFiltrada()->orderBy('fecha')->orderBy('origen_tipo')->paginate(25);

        // Totales de TODO lo filtrado (no solo la página visible)
        $totales = $this->consultaFiltrada()
            ->selectRaw('COUNT(*) as registros, COALESCE(SUM(costo_total),0) as costo, COALESCE(SUM(minutos),0) as minutos, COALESCE(SUM(cantidad_jornales),0) as jornales')
            ->first();

        $totalesPorTipo = $this->consultaFiltrada()
            ->selectRaw('origen_tipo, COUNT(*) as registros, SUM(costo_total) as costo')
            ->groupBy('origen_tipo')
            ->orderByDesc('costo')
            ->get();

        return view('livewire.costos.campo-costos-component', compact('resumenes', 'totales', 'totalesPorTipo'));
    }

    private function consultaFiltrada()
    {
        $query = ResumenCostoDiario::query();

        // Sin ningún tipo marcado no se muestra nada (permite "desmarcar todos" y elegir uno).
        if (empty($this->tiposSeleccionados)) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->filtro) {
            $texto = trim($this->filtro);
            $query->where(function ($q) use ($texto) {
                $q->where('trabajador', 'like', "%{$texto}%")
                    ->orWhere('labor_nombre', 'like', "%{$texto}%")
                    ->orWhere('labor', 'like', "%{$texto}%")
                    ->orWhere('insumo_nombre', 'like', "%{$texto}%");
            });
        }

        if ($this->filtroCampo) {

            $query->where('campo', $this->filtroCampo);
        }

        if ($this->campaniaId) {
            $query->where('campania', CampoCampania::find($this->campaniaId)?->nombre_campania);
        }

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        }

        if (count($this->tiposSeleccionados) < count($this->tiposDisponibles)) {
            $query->whereIn('origen_tipo', $this->tiposSeleccionados);
        }

        return $query;
    }
}