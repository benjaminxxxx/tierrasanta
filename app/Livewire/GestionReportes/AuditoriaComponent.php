<?php

namespace App\Livewire\GestionReportes;

use App\Models\Auditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AuditoriaComponent extends Component
{
    use WithPagination;

    // ── Filtros ──────────────────────────────────────────────────────
    public string $fechaDesde  = '';
    public string $fechaHasta  = '';
    public string $modelo      = '';
    public string $accion      = '';
    public string $usuario     = '';
    public string $busqueda    = '';

    // ── Opciones para selects ────────────────────────────────────────
    public array $modelosDisponibles  = [];
    public array $usuariosDisponibles = [];

    protected $queryString = [
        'fechaDesde', 'fechaHasta', 'modelo', 'accion', 'usuario', 'busqueda',
    ];

    public function mount(): void
    {
        $this->fechaDesde = now()->subDays(7)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
        $this->cargarOpciones();
    }

    private function cargarOpciones(): void
    {
        $this->modelosDisponibles = Auditoria::select('modelo')
            ->distinct()
            ->orderBy('modelo')
            ->pluck('modelo')
            ->map(fn($m) => [
                'valor'     => $m,
                'etiqueta'  => class_basename($m),
            ])
            ->toArray();

        $this->usuariosDisponibles = Auditoria::select('usuario_id', 'usuario_nombre')
            ->distinct()
            ->orderBy('usuario_nombre')
            ->get()
            ->map(fn($u) => [
                'valor'    => $u->usuario_id,
                'etiqueta' => $u->usuario_nombre,
            ])
            ->toArray();
    }

    private function query()
    {
        return Auditoria::query()
            ->when($this->fechaDesde, fn($q) =>
                $q->where('fecha_accion', '>=', $this->fechaDesde . ' 00:00:00'))
            ->when($this->fechaHasta, fn($q) =>
                $q->where('fecha_accion', '<=', $this->fechaHasta . ' 23:59:59'))
            ->when($this->modelo, fn($q) =>
                $q->where('modelo', $this->modelo))
            ->when($this->accion, fn($q) =>
                $q->where('accion', $this->accion))
            ->when($this->usuario, fn($q) =>
                $q->where('usuario_id', $this->usuario))
            ->when($this->busqueda, fn($q) =>
                $q->where(function ($sub) {
                    $sub->where('usuario_nombre', 'like', "%{$this->busqueda}%")
                        ->orWhere('observacion', 'like', "%{$this->busqueda}%")
                        ->orWhere('modelo_id', 'like', "%{$this->busqueda}%");
                }))
            ->orderByDesc('fecha_accion');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['modelo', 'accion', 'usuario', 'busqueda']);
        $this->fechaDesde = now()->subDays(7)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
        $this->resetPage();
    }

    // Resetear página al cambiar cualquier filtro
    public function updatedFechaDesde():  void { $this->resetPage(); }
    public function updatedFechaHasta():  void { $this->resetPage(); }
    public function updatedModelo():      void { $this->resetPage(); }
    public function updatedAccion():      void { $this->resetPage(); }
    public function updatedUsuario():     void { $this->resetPage(); }
    public function updatedBusqueda():    void { $this->resetPage(); }

    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Sin paginación — todos los registros del filtro actual
        $registros = $this->query()->get();

        $pdf = Pdf::loadView('reportes.auditoria-pdf', [
            'registros'   => $registros,
            'filtros'     => [
                'desde'   => $this->fechaDesde,
                'hasta'   => $this->fechaHasta,
                'modelo'  => $this->modelo ? class_basename($this->modelo) : 'Todos',
                'accion'  => $this->accion  ?: 'Todas',
                'usuario' => $this->usuario ?: 'Todos',
            ],
            'generadoEn'  => now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'auditoria_' . now()->format('Y-m-d_H-i') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.gestion-reportes.auditoria-component', [
            'registros' => $this->query()->paginate(20),
            'total'     => $this->query()->count(),
        ]);
    }
}