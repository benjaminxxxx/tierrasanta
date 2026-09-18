<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\InsCategoria;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\AuditoriaServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AlmacenSalidaListaComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination, HandlesAlerts;

    public $anio;
    public $mes;
    public string $tipo;

    public array $filtros = [
        'dia' => '',
        'productoId' => '',
        'destinoId' => '',
        'categoria' => '',
    ];

    public bool $modalAuditoriaSalida = false;
    public array $auditoriaHistorialSalida = [];

    public array $listaProductos = [];
    public array $listaMaquinarias = [];
    public array $listaCampos = [];
    public array $registros = [];
    public array $listaGruposOperativos = [];

    protected $listeners = [
        'salidaGuardada' => 'alSalidaGuardada',
    ];

    public function mount($mes = null, $anio = null, string $tipo)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->tipo = $tipo;
        $this->cargarListas();
        $this->obtenerSalidasPaginadas();
        $this->notificarTabla();
        $this->listaGruposOperativos = InsCategoria::query()
            ->whereNotNull('grupo_operativo')
            ->where('grupo_operativo', '!=', '')
            ->select('grupo_operativo')
            ->distinct()
            ->orderBy('grupo_operativo')
            ->pluck('grupo_operativo')
            ->toArray();
    }

    public function updatingFiltros(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->filtros = ['dia' => '', 'productoId' => '', 'destinoId' => '', 'categoria' => ''];
        $this->resetPage();
    }

    // ────────────────────────────────────────────────────────────
    // Abrir el modal de formulario (crear o editar según haya selección)
    // ────────────────────────────────────────────────────────────
    public function abrirCrear(): void
    {
        $this->dispatch('abrirFormularioSalida', tipo: $this->tipo, mes: $this->mes, anio: $this->anio, ids: []);
    }

    /**
     * Abre el formulario de edición pasando una lista de IDs explícita (desde JS / Context Menu).
     */
    public function editarSalidas(array $ids): void
    {
        if (empty($ids)) {
            $this->alert('warning', 'No se enviaron registros válidos para editar.');
            return;
        }

        $this->dispatch(
            'abrirFormularioSalida',
            tipo: $this->tipo,
            mes: $this->mes,
            anio: $this->anio,
            ids: $ids
        );
    }

    /**
     * El formulario avisa con qué mes/año quedó el registro guardado —
     * saltamos automáticamente a ese periodo para que el usuario vea
     * de inmediato lo que acaba de guardar, aunque estuviera viendo otro mes.
     */
    public function alSalidaGuardada($mes, $anio): void
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->resetPage();
    }

    public function verHistorialSalida(int $id): void
    {
        $this->auditoriaHistorialSalida = AuditoriaServicio::getAuditoria(AlmacenProductoSalida::class, $id);
        $this->modalAuditoriaSalida = true;
    }
    public function eliminarSalidas(array $ids): void
    {
        try {
            AlmacenServicio::eliminarSalidasAgrupadas($ids);
            $this->alert('success', count($ids) === 1 ? 'Salida eliminada.' : 'Salidas eliminadas correctamente.');
            $this->notificarTabla();
        } catch (\Exception $e) {
            $this->errorAlert('error', $e->getMessage());
        }
    }

    public function cargarListas(): void
    {
        $this->listaCampos = Campo::get()->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])->toArray();
        $this->listaProductos = Producto::deTipo($this->tipo)->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])->toArray();

        if ($this->tipo === 'combustible') {
            $this->listaMaquinarias = Maquinaria::orderBy('nombre')->get()
                ->map(fn($m) => ['id' => $m->id, 'label' => $m->nombre])->toArray();
        }
    }
    public function updatedPage()
    {
        $this->notificarTabla();
    }
    /**
     * Obtiene los registros paginados y despacha los datos formateados a Handsontable
     */
    public function notificarTabla()
    {
        $salidas = $this->obtenerSalidasPaginadas();
        $dataFormatted = $this->formatearParaHandsontable($salidas);
        $this->registros = $dataFormatted;
        $this->dispatch('actualizarTabla', data: $dataFormatted);
    }
    private function obtenerSalidasPaginadas()
    {
        return AlmacenServicio::obtenerRegistrosPorFecha(
            $this->mes,
            $this->anio,
            $this->tipo,
            null,               // $tipoKardex
            $this->filtros      // Pass los filtros dinámicos
        );
    }
    public function updatedFiltros()
    {
        $this->resetPage();
    }
    private function formatearParaHandsontable($salidas): array
    {
        // Transforma la colección Eloquent/Paginador a una estructura plana de Array
        $items = method_exists($salidas, 'items') ? $salidas->items() : $salidas;

        return collect($items)->map(function ($item) {
            return [
                'id' => $item->id,
                'fecha_reporte' => $item->fecha_reporte ? \Carbon\Carbon::parse($item->fecha_reporte)->format('Y-m-d') : null,
                'producto' => $item->producto?->nombre_comercial ?? '',
                'unidad_medida' => $item->producto?->unidadMedida?->codigo ?? 'UND',
                'cantidad' => (float) $item->cantidad,
                'maquinaria_id' => $item->maquinaria?->nombre ?? '',
                'campo_nombre' => $item->campo_nombre ?? '',
                'tipo_kardex' => $item->tipo_kardex ?? '',
                // En el método formatearParaHandsontable:
                'categoria' => $item->producto?->categoria?->grupo_operativo ?? '',
                'costo_por_kg' => (float) ($item->costo_por_kg ?? 0),
                'total_costo' => (float) ($item->total_costo ?? 0),
                'distribuciones_count' => (int) ($item->distribuciones_count ?? 0),
            ];
        })->toArray();
    }
    public function render()
    {
        return view('livewire.almacen-salida-lista-component', [
            'salidas' => $this->obtenerSalidasPaginadas(),
        ]);
    }
}