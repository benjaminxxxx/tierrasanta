<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\AuditoriaServicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CamposComponent extends Component
{
    use LivewireAlert, WithPagination;

    // ── Formulario ───────────────────────────────────────────────────
    public $campoNombre;
    public $campoNombreEditar;
    public $campoPadre;
    public $area;
    public $alias;
    public bool $mostrarFormulario = false;
    public bool $estaEditando      = false;

    // ── Filtros ──────────────────────────────────────────────────────
    public string $filtroCampo  = '';
    public string $filtroEstado = 'todos';

    // ── Auditoría ────────────────────────────────────────────────────
    public bool   $modalAuditoria      = false;
    public array  $auditoriaHistorial  = [];
    public string $campoAuditoriaLabel = '';

    protected $listeners = ['campaniaInsertada' => '$refresh'];

    // ── Datos de estado calculados (reutilizados en render y export) ─
    private function obtenerCamposActivos(): \Illuminate\Support\Collection
    {
        return CampoCampania::whereNull('fecha_fin')->pluck('campo')->unique()->values();
    }

    private function obtenerCamposConAlerta(): \Illuminate\Support\Collection
    {
        $limiteAlerta = Carbon::today()->subYears(2);
        return CampoCampania::whereNull('fecha_fin')
            ->where('fecha_inicio', '<', $limiteAlerta)
            ->pluck('campo')->unique()->values();
    }

    private function buildQuery(\Illuminate\Support\Collection $camposActivos, \Illuminate\Support\Collection $camposConAlerta)
    {
        return Campo::orderBy('orden')
            ->when($this->filtroCampo, fn($q) => $q->where('nombre', $this->filtroCampo))
            ->when($this->filtroEstado === 'activo',   fn($q) => $q->whereIn('nombre', $camposActivos))
            ->when($this->filtroEstado === 'inactivo', fn($q) => $q->whereNotIn('nombre', $camposActivos))
            ->when($this->filtroEstado === 'alerta',   fn($q) => $q->whereIn('nombre', $camposConAlerta))
            ->with([
                'campanias' => fn($q) => $q->orderByDesc('fecha_inicio'),
                'siembras',
            ]);
    }

    private function enriquecerColeccion($coleccion, $camposActivos, $camposConAlerta)
    {
        return $coleccion->transform(function ($campo) use ($camposActivos, $camposConAlerta) {
            $campo->es_activo      = $camposActivos->contains($campo->nombre);
            $campo->tiene_alerta   = $camposConAlerta->contains($campo->nombre);
            $campo->campana_activa = $campo->campanias->firstWhere('fecha_fin', null);
            $campo->ultima_campana = $campo->campanias->first();
            $campo->total_campanas = $campo->campanias->count();
            $campo->ultima_siembra = $campo->siembras->first();
            $campo->total_siembras = $campo->siembras->count();
            return $campo;
        });
    }

    // ── Render ───────────────────────────────────────────────────────
    public function render()
    {
        $camposActivos   = $this->obtenerCamposActivos();
        $camposConAlerta = $this->obtenerCamposConAlerta();

        $paginados = $this->buildQuery($camposActivos, $camposConAlerta)->paginate(25);
        $this->enriquecerColeccion($paginados->getCollection(), $camposActivos, $camposConAlerta);

        $totales = [
            'todos'    => Campo::count(),
            'activos'  => $camposActivos->count(),
            'alertas'  => $camposConAlerta->count(),
            'inactivos'=> Campo::count() - $camposActivos->count(),
        ];

        return view('livewire.campos-component', [
            'campos'  => $paginados,
            'totales' => $totales,
        ]);
    }

    // ── Filtros ──────────────────────────────────────────────────────
    public function updatedFiltroCampo():  void { $this->resetPage(); }
    public function updatedFiltroEstado(): void { $this->resetPage(); }

    public function setFiltroEstado(string $estado): void
    {
        $this->filtroEstado = $estado;
        $this->resetPage();
    }

    // ── Export PDF ───────────────────────────────────────────────────
    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $camposActivos   = $this->obtenerCamposActivos();
        $camposConAlerta = $this->obtenerCamposConAlerta();

        $todos = $this->buildQuery($camposActivos, $camposConAlerta)->get();
        $this->enriquecerColeccion($todos, $camposActivos, $camposConAlerta);

        $etiquetaFiltro = match($this->filtroEstado) {
            'activo'   => 'Campos activos',
            'inactivo' => 'Campos sin campaña activa',
            'alerta'   => 'Campos con posible campaña sin cerrar',
            default    => 'Todos los campos',
        };

        $pdf = Pdf::loadView('reportes.campos-pdf', [
            'campos'         => $todos,
            'etiquetaFiltro' => $etiquetaFiltro,
            'generadoEn'     => now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
            'filtroCampo'    => $this->filtroCampo ?: null,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'campos_' . now()->format('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // ── Formulario ───────────────────────────────────────────────────
    public function registrarCampo(): void
    {
        $this->resetErrorBag();
        $this->estaEditando      = false;
        $this->campoNombre       = null;
        $this->campoPadre        = null;
        $this->area              = null;
        $this->alias             = null;
        $this->campoNombreEditar = null;
        $this->mostrarFormulario = true;
    }

    public function editarCampo(string $campoNombre): void
    {
        $this->resetErrorBag();
        $campo = Campo::where('nombre', $campoNombre)->first();
        if (!$campo) return;

        $this->estaEditando      = true;
        $this->campoNombreEditar = $campoNombre;
        $this->campoNombre       = $campoNombre;
        $this->campoPadre        = $campo->campo_parent_nombre;
        $this->area              = $campo->area;
        $this->alias             = $campo->alias;
        $this->mostrarFormulario = true;
    }

    public function storeCampos(): void
    {
        $this->validate([
            'campoNombre' => 'required|unique:campos,nombre,' . $this->campoNombreEditar . ',nombre',
            'area'        => 'required',
        ], [
            'campoNombre.required' => 'El nombre del campo es obligatorio.',
            'campoNombre.unique'   => 'Este nombre de campo ya existe.',
            'area.required'        => 'El área es obligatoria.',
        ]);

        $data = [
            'nombre'              => $this->campoNombre,
            'area'                => $this->area,
            'campo_parent_nombre' => $this->campoPadre ?: null,
            'alias'               => $this->alias,
        ];

        $ignorados = ['created_at', 'updated_at', 'pos_x', 'pos_y', 'grupo', 'orden', 'estado', 'etapa'];

        if ($this->estaEditando) {
            $campo = Campo::find($this->campoNombreEditar);
            if ($campo) {
                $antes = $campo->toArray();

                if ($this->campoNombre !== $this->campoNombreEditar) {
                    $campo->delete();
                    $nuevo = Campo::create($data);
                    AuditoriaServicio::registrar(
                        modelo: Campo::class,
                        modeloId: $nuevo->nombre,           // string ✓
                        accion: 'crear',
                        despues: $nuevo->toArray(),
                        observacion: "Renombrado desde {$this->campoNombreEditar}",
                        camposIgnorados: $ignorados,
                    );
                } else {
                    $campo->update($data);
                    AuditoriaServicio::registrar(
                        modelo: Campo::class,
                        modeloId: $campo->nombre,           // string ✓
                        accion: 'editar',
                        antes: $antes,
                        despues: $campo->fresh()->toArray(),
                        camposIgnorados: $ignorados,
                    );
                }
            }
        } else {
            $nuevo = Campo::create($data);
            AuditoriaServicio::registrar(
                modelo: Campo::class,
                modeloId: $nuevo->nombre,                   // string ✓
                accion: 'crear',
                despues: $nuevo->toArray(),
                camposIgnorados: $ignorados,
            );
        }

        $this->reset(['campoNombre', 'area', 'campoPadre', 'alias', 'campoNombreEditar', 'estaEditando']);
        $this->alert('success', 'Campo guardado correctamente.');
        $this->mostrarFormulario = false;
    }

    // ── Auditoría ────────────────────────────────────────────────────
    public function verAuditoria(string $campoNombre): void
    {
        $this->campoAuditoriaLabel = $campoNombre;
        $this->auditoriaHistorial  = AuditoriaServicio::getAuditoria(
            Campo::class,
            $campoNombre                                    // string ✓
        );
        $this->modalAuditoria = true;
    }
}