<?php

namespace App\Livewire\Campania;

use App\Models\CampaniaInformeMensual;
use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualConsulta;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualExcel;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualFormatoReglas;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualReglas;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use App\Services\Campania\Resumen\CampaniaResumenConsulta;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Campañas (antes "Resumen general"): punto central del ciclo. Lista de campañas con su avance, etapa y estado de
 * cosecha, y desde cada una: informe al día (o final si está cerrada), informes mensuales, detalle por campo,
 * editar, cerrar y eliminar. La vista "Tabla técnica" mantiene el cuadro completo de columnas por bloque.
 */
#[Title('Campañas')]
class CampaniasComponent extends Component
{
    use WithPagination, WithoutUrlPagination, LivewireAlert, \App\Traits\HandlesAlerts;

    public $campoSeleccionado;
    public $campaniaSeleccionada;
    #[Url(except: '')]
    public string $buscar = '';

    /** vigentes (por defecto) | cerradas | todas */
    public string $estado = CampaniaResumenConsulta::ESTADO_VIGENTES;

    /** Solo campañas vigentes a las que se recomienda cerrar (ya cosechadas). */
    public bool $soloPorCerrar = false;

    /** lista (tarjetas compactas) | tabla (cuadro técnico completo) */
    #[Url(except: 'lista')]
    public string $vista = 'lista';

    public bool $mostrarInforme = false;
    public ?int $informeCampaniaId = null;

    public array $breadcrumb = [];
    public array $campanias = [];

    /** Contadores del encabezado (se recalculan al cambiar de campo o al guardar, no en cada render). */
    public array $totales = ['vigentes' => 0, 'cerradas' => 0, 'por_cerrar' => 0];

    protected $listeners = ['campaniaInsertada' => 'refrescar'];

    public function mount(): void
    {
        $this->breadcrumb = [['label' => 'Campañas']];

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

    public function updatedBuscar(): void
    {
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

    public function limpiarFiltros(): void
    {
        $this->reset(['buscar', 'campaniaSeleccionada', 'soloPorCerrar']);
        $this->campoSeleccionado = null;
        $this->estado = CampaniaResumenConsulta::ESTADO_VIGENTES;
        session()->forget(['campo', 'campania']);
        $this->resetPage();
        $this->cargarOpciones();
    }

    public function refrescar(): void
    {
        $this->resetPage();
        $this->cargarOpciones();
    }

    /** Informe al día (campaña abierta) o final (cerrada), calculado en el momento, sin guardar. */
    public function verInforme(int $campaniaId): void
    {
        abort_unless(auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL), 403);
        $this->informeCampaniaId = $campaniaId;
        $this->mostrarInforme = true;
    }

    public function descargarInforme(int $campaniaId)
    {
        try {
            abort_unless(auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL), 403);
            $informe = app(CampaniaInformeMensualConsulta::class)->enVivo(CampoCampania::findOrFail($campaniaId));
            $excel = app(CampaniaInformeMensualExcel::class);
            $libro = $excel->generar($informe);
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $excel->nombreArchivo($informe));
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function eliminarCampania($campaniaId): void
    {
        try {
            // Auditado: queda en `auditorias` quién eliminó y la campaña completa
            app(CampaniaRegistroProceso::class)->eliminar((int) $campaniaId);
            $this->alert('success', 'Campaña eliminada correctamente.');
            $this->cargarOpciones();
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function descargarReporteCampania()
    {
        try {
            $registros = app(CampaniaResumenConsulta::class)->todas($this->filtros());
            return app(CampaniaServicio::class)
                ->descargarReporteCampania($registros, $this->campoSeleccionado, $this->campaniaSeleccionada);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    private function filtros(): array
    {
        return [
            'campo' => $this->campoSeleccionado ?: null,
            'campania' => $this->campaniaSeleccionada ?: null,
            'buscar' => $this->buscar,
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

    /**
     * Avance vegetativo de cada campaña de la página (hoy o a su cierre).
     *
     * @return array<int, array>
     */
    private function avances($campanias): array
    {
        $hoy = now()->toDateString();
        $avances = [];
        foreach ($campanias as $c) {
            $corte = $c->fecha_fin ? min(Carbon::parse($c->fecha_fin)->toDateString(), $hoy) : $hoy;
            $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
            if ($inicio > $corte) {
                continue;
            }
            $infestacion = $c->infestacion_fecha ? Carbon::parse($c->infestacion_fecha)->toDateString() : null;
            $avances[$c->id] = CampaniaInformeMensualReglas::avanceVegetativo($inicio, $infestacion && $infestacion <= $corte ? $infestacion : null, $corte);
        }
        return $avances;
    }

    public function render()
    {
        $resultado = app(CampaniaResumenConsulta::class)->listar($this->filtros());
        $pagina = collect($resultado['campanias']->items());

        // Último informe mensual guardado de cada campaña de la página
        $ultimosInformes = CampaniaInformeMensual::whereIn('campo_campania_id', $pagina->pluck('id'))
            ->get(['id', 'campo_campania_id', 'anio', 'mes', 'version', 'es_cierre'])
            ->groupBy('campo_campania_id')
            ->map(fn($g) => ['ultimo' => $g->sortByDesc('periodo')->first(), 'cantidad' => $g->count()]);

        $informe = null;
        $tablas = [];
        if ($this->mostrarInforme && $this->informeCampaniaId) {
            try {
                $informe = app(CampaniaInformeMensualConsulta::class)->enVivo(CampoCampania::findOrFail($this->informeCampaniaId));
                $tablas = CampaniaInformeMensualFormatoReglas::tablas($informe->datos + ['advertencias' => $informe->advertencias ?? []]);
            } catch (\Throwable $th) {
                $this->mostrarInforme = false;
                $this->errorAlert($th);
            }
        }

        return view('livewire.campania.campanias-component', [
            'campaniasGenerales' => $resultado['campanias'],
            'cosecha' => $resultado['cosecha'],
            // Etapa en que está cada campaña de la página (por sus fechas registradas)
            'etapas' => app(\App\Services\Campania\Etapa\CampaniaEtapaConsulta::class)->etapasActuales($pagina),
            'avances' => $this->avances($pagina),
            'ultimosInformes' => $ultimosInformes,
            'informe' => $informe,
            'tablas' => $tablas,
        ]);
    }
}
