<?php

namespace App\Livewire\Campania;

use App\Services\Campania\CampaniaServicio;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use App\Services\Campania\Resumen\CampaniaResumenConsulta;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

#[Title('Resumen de Campaña')]
class CampaniasComponent extends Component
{
    use WithPagination, WithoutUrlPagination, LivewireAlert;

    public $campoSeleccionado;
    public $campaniaSeleccionada;

    /** vigentes (por defecto) | cerradas | todas */
    public string $estado = CampaniaResumenConsulta::ESTADO_VIGENTES;

    /** Solo campañas vigentes a las que se recomienda cerrar (ya cosechadas). */
    public bool $soloPorCerrar = false;

    public array $breadcrumb = [];
    public array $campanias = [];

    /** Contadores del encabezado (se recalculan al cambiar de campo o al guardar, no en cada render). */
    public array $totales = ['vigentes' => 0, 'cerradas' => 0, 'por_cerrar' => 0];

    protected $listeners = ['campaniaInsertada' => 'refrescar'];

    public function mount(): void
    {
        $this->breadcrumb = [['label' => 'Resumen general de campañas']];

        // Enlaces de tareas pendientes: ?campo=A5 filtra el campo; ?cerrar=123 abre el cierre de esa campaña
        $campo = request()->query('campo');
        if (is_string($campo) && $campo !== '') {
            session(['campo' => $campo]);
            $this->estado = CampaniaResumenConsulta::ESTADO_TODAS;
        }
        $this->campoSeleccionado = session('campo');
        $this->cargarOpciones();

        $cerrar = (int) request()->query('cerrar');
        if ($cerrar > 0) {
            $this->dispatch('cerrarCampania', campaniaId: $cerrar);
        }
    }

    public function updatedCampoSeleccionado($campo): void
    {
        session(['campo' => $campo]);
        $this->resetPage();
        $this->cargarOpciones();
    }

    public function updatedCampaniaSeleccionada($campania): void
    {
        session(['campania' => $campania]);
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        if ($this->estado !== CampaniaResumenConsulta::ESTADO_VIGENTES) {
            $this->soloPorCerrar = false;
        }
        $this->resetPage();
    }

    public function updatedSoloPorCerrar(): void
    {
        if ($this->soloPorCerrar) {
            $this->estado = CampaniaResumenConsulta::ESTADO_VIGENTES;
        }
        $this->resetPage();
    }

    /** Atajos de los contadores del encabezado. */
    public function filtrarEstado(string $estado, bool $porCerrar = false): void
    {
        $this->estado = $estado;
        $this->soloPorCerrar = $porCerrar;
        $this->resetPage();
    }

    public function refrescar(): void
    {
        $this->resetPage();
        $this->cargarOpciones();
    }

    public function eliminarCampania($campaniaId): void
    {
        try {
            // Auditado: queda en `auditorias` quién eliminó y la campaña completa
            app(CampaniaRegistroProceso::class)->eliminar((int) $campaniaId);
            $this->alert('success', 'Campaña eliminada correctamente.');
            $this->cargarOpciones();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function descargarReporteCampania()
    {
        try {
            $registros = app(CampaniaResumenConsulta::class)->todas($this->filtros());
            return app(CampaniaServicio::class)
                ->descargarReporteCampania($registros, $this->campoSeleccionado, $this->campaniaSeleccionada);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    private function filtros(): array
    {
        return [
            'campo' => $this->campoSeleccionado ?: null,
            'campania' => $this->campaniaSeleccionada ?: null,
            'estado' => $this->estado,
            'por_cerrar' => $this->soloPorCerrar,
        ];
    }

    private function cargarOpciones(): void
    {
        $consulta = app(CampaniaResumenConsulta::class);
        $this->totales = $consulta->totales($this->campoSeleccionado ?: null);

        $this->campanias = $this->campoSeleccionado ? $consulta->nombresPorCampo($this->campoSeleccionado) : [];
        $campaniaSesion = session('campania');
        $this->campaniaSeleccionada = $campaniaSesion && array_key_exists($campaniaSesion, $this->campanias)
            ? $campaniaSesion
            : null;
    }

    public function render()
    {
        $resultado = app(CampaniaResumenConsulta::class)->listar($this->filtros());

        return view('livewire.campania.campanias-component', [
            'campaniasGenerales' => $resultado['campanias'],
            'cosecha' => $resultado['cosecha'],
            // Etapa en que está cada campaña de la página (por sus fechas registradas)
            'etapas' => app(\App\Services\Campania\Etapa\CampaniaEtapaConsulta::class)->etapasActuales(collect($resultado['campanias']->items())),
        ]);
    }
}
