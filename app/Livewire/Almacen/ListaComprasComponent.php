<?php

namespace App\Livewire\Almacen;

use App\Constants\Permisos;
use App\Models\Compra;
use App\Services\Almacen\Compra\AlmacenCompraConsulta;
use App\Services\Almacen\Compra\AlmacenCompraProceso;
use App\Traits\HandlesAlerts;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/**
 * Lista de compras (DESIGN.md). Dos vistas:
 * - Compras: cada comprobante con sus productos y cantidades;
 * - Por producto: las líneas de los productos buscados (en qué compras, cuánto y a qué costo) con totales.
 * Eliminar y restaurar piden motivo y quedan en la auditoría (AlmacenCompraProceso).
 */
#[Title('Compras')]
class ListaComprasComponent extends Component
{
    use WithPagination, WithoutUrlPagination, LivewireAlert, HandlesAlerts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $producto = '';

    #[Url]
    public string $tipoComprobante = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    #[Url]
    public string $vista = 'compras';

    public ?int $proveedorId = null;
    public ?string $proveedorLabel = null;

    public string $sortField = 'fecha_emision';
    public string $sortDirection = 'desc';
    public bool $verEliminadas = false;

    /** Modal eliminar / restaurar: compra, acción y motivo. */
    public bool $modalMotivo = false;
    public string $accionMotivo = 'eliminar';
    public ?int $compraMotivoId = null;
    public string $motivo = '';

    /** Modal historial de una compra. */
    public bool $modalHistorial = false;
    public array $historial = [];
    public string $historialTitulo = '';

    protected $listeners = ['nuevaCompraActualizada' => '$refresh', 'nuevaCompraRegistrada'];

    /** /compras/{producto_id}: abre la vista "Por producto" de ese producto. */
    public function mount($producto_id = null): void
    {
        if ($producto_id && ($nombre = \App\Models\Producto::withTrashed()->whereKey($producto_id)->value('nombre_comercial'))) {
            $this->producto = $nombre;
            $this->vista = 'producto';
        }
    }

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'purchase-filter-supplier') {
            $this->proveedorId = $id;
            $this->proveedorLabel = $label;
            $this->resetPage();
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'purchase-filter-supplier') {
            $this->proveedorId = null;
            $this->proveedorLabel = null;
            $this->resetPage();
        }
    }

    public function nuevaCompraRegistrada(): void
    {
        $this->resetPage();
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['search', 'producto', 'tipoComprobante', 'fechaDesde', 'fechaHasta', 'verEliminadas', 'vista'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'producto', 'tipoComprobante', 'fechaDesde', 'fechaHasta', 'proveedorId', 'proveedorLabel');
        $this->resetPage();
    }

    /** Desde un producto de la lista: ver todas sus compras. */
    public function verProducto(string $nombre): void
    {
        $this->producto = $nombre;
        $this->vista = 'producto';
        $this->resetPage();
    }

    private function filtros(): array
    {
        return [
            'buscar' => $this->search,
            'producto' => $this->producto,
            'tipoComprobante' => $this->tipoComprobante,
            'proveedorId' => $this->proveedorId,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
        ];
    }

    // ── Eliminar / restaurar con motivo ─────────────────────────

    public function pedirMotivo(int $compraId, string $accion): void
    {
        $this->resetErrorBag();
        $this->compraMotivoId = $compraId;
        $this->accionMotivo = $accion === 'restaurar' ? 'restaurar' : 'eliminar';
        $this->motivo = '';
        $this->modalMotivo = true;
    }

    public function confirmarMotivo(): void
    {
        abort_unless(auth()->user()?->can(Permisos::INSUMO_COMPRA_GESTIONAR), 403, 'No tienes permiso para gestionar compras.');
        try {
            $proceso = app(AlmacenCompraProceso::class);
            if ($this->accionMotivo === 'restaurar') {
                $proceso->restaurar($this->compraMotivoId, $this->motivo);
                $this->alert('success', 'Compra restaurada y stock reingresado.');
            } else {
                $proceso->eliminar($this->compraMotivoId, $this->motivo);
                $this->alert('success', 'Compra eliminada y stock descontado. Puedes verla en "Ver eliminadas".');
            }
            $this->modalMotivo = false;
        } catch (ValidationException $e) {
            $this->addError('motivo', $e->validator->errors()->first('motivo'));
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function verHistorial(int $compraId): void
    {
        $compra = Compra::withTrashed()->find($compraId);
        $this->historialTitulo = $compra ? trim(($compra->serie ? $compra->serie . '-' : '') . $compra->numero) : "#{$compraId}";
        $this->historial = app(AlmacenCompraConsulta::class)->historial($compraId);
        $this->modalHistorial = true;
    }

    public function render()
    {
        $consulta = app(AlmacenCompraConsulta::class);
        $porProducto = $this->vista === 'producto' && trim($this->producto) !== '';

        $compras = $porProducto ? null : $consulta->lista($this->filtros(), $this->verEliminadas, $this->sortField, $this->sortDirection);
        $detalle = $porProducto ? $consulta->lineasDeProducto($this->filtros(), $this->verEliminadas) : null;
        $eliminaciones = $this->verEliminadas && $compras ? $consulta->eliminaciones($compras->pluck('id')->all()) : [];

        $compraMotivo = $this->modalMotivo && $this->compraMotivoId
            ? Compra::withTrashed()->with(['proveedor.persona', 'detalles.producto'])->find($this->compraMotivoId)
            : null;

        return view('livewire.almacen.lista-compras-component', compact('compras', 'detalle', 'eliminaciones', 'compraMotivo', 'porProducto'));
    }
}
