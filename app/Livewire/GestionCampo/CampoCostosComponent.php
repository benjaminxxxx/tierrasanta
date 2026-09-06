<?php

namespace App\Livewire\GestionCampo;

use App\Models\Campania;
use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Campo\Costos\ConsolidarCostoGastosGeneralesServicio;
use App\Services\Campo\Costos\ConsolidarCostoInsumosServicio;
use App\Services\Campo\Costos\ConsolidarCostoManoObraServicio;
use App\Services\Campo\Costos\ConsolidarCostoMaquinariaServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
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

    public $tiposDisponibles = ['planilla', 'cuadrilla', 'maquinaria', 'fertilizante', 'pesticida', 'costo_fijo', 'costo_operativo'];
    public $tiposSeleccionados = [];
    public $filtroCampo = null;
    public $campaniasDelCampo = [];
    public $filtro = '';
    public $reporteFileCampania = null;
    public function mount()
    {
        $this->campanias = CampoCampania::orderByDesc('fecha_inicio')->get();
        $this->tiposSeleccionados = $this->tiposDisponibles; // todos visibles por defecto
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

            // 1. Generar la BDD Mensual y actualizar la ruta del reporte en la BD
            app(CampaniaServicio::class)->generarBddMensual($campania->id);

            // 2. Refrescar la colección en memoria con los datos recién guardados
            $this->cargarCampaniasPorCampo($this->filtroCampo);

            // 3. Actualizar los estados locales (fechaInicio, fechaFin, reporteFileCampania)
            $this->buscarReporte($this->campaniaId);

            $this->alert('success', "Consolidado: {$totalPlanilla} planilla, {$totalGastosGenerales} gastos generales, {$totalMaquinaria} maquinaria.");
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
        $query = ResumenCostoDiario::query();
        if ($this->filtro) {
            $texto = trim($this->filtro);
            $query->where(function ($q) use ($texto) {
                $q->where('trabajador', 'like', "%{$texto}%")
                    ->orWhere('labor_nombre', 'like', "%{$texto}%")
                    ->orWhere('labor', 'like', "%{$texto}%");
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

        if (!empty($this->tiposSeleccionados) && count($this->tiposSeleccionados) < count($this->tiposDisponibles)) {
            $query->whereIn('origen_tipo', $this->tiposSeleccionados);
        }

        $resumenes = $query->orderBy('fecha')->orderBy('origen_tipo')->paginate(25);

        return view('livewire.gestion-campo.campo-costos-component', compact('resumenes'));
    }
}