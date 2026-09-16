<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Services\Almacen\StockService;
use App\Traits\HandlesAlerts;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use App\Services\Purchase\ExportToExcelService;

#[Title('Compras')]
class ListaComprasComponent extends Component
{
    use WithPagination, WithoutUrlPagination, LivewireAlert, HandlesAlerts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $tipoComprobante = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    public ?int $proveedorId = null;
    public ?string $proveedorLabel = null;

    public string $sortField = 'fecha_emision';
    public string $sortDirection = 'desc';
    public bool $verEliminadas = false;

    protected $listeners = ['nuevaCompraActualizada' => '$refresh', 'nuevaCompraRegistrada'];

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
    public function nuevaCompraRegistrada()
    {
        $this->resetPage();
    }
    public function sortBy($field)
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
        $this->reset('search', 'tipoComprobante', 'fechaDesde', 'fechaHasta', 'proveedorId', 'proveedorLabel');
        $this->resetPage();
    }

    public function getComprasProperty()
    {
        return Compra::query()
            ->with(['proveedor.persona', 'almacen'])
            ->when($this->verEliminadas, fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('numero', 'like', "%{$this->search}%")
                        ->orWhere('serie', 'like', "%{$this->search}%")
                        ->orWhereHas('proveedor.persona', fn($p) => $p->where('razon_social', 'like', "%{$this->search}%")
                            ->orWhere('nombres', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->tipoComprobante, fn($q) => $q->where('tipo_comprobante_codigo', $this->tipoComprobante))
            ->when($this->proveedorId, fn($q) => $q->where('proveedor_id', $this->proveedorId))
            ->when($this->fechaDesde, fn($q) => $q->whereDate('fecha_emision', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn($q) => $q->whereDate('fecha_emision', '<=', $this->fechaHasta))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function deletePurchase(int $purchaseId): void
    {
        try {
            DB::transaction(function () use ($purchaseId) {
                $compra = Compra::findOrFail($purchaseId);

                // 1. Revertir el stock y eliminar/anular los movimientos asociados
                // Si algún movimiento ya fue procesado en Kardex, lanzará un RuntimeException
                app(StockService::class)->revertirMovimientosDeOrigen(
                    origenType: Compra::class,
                    origenId: $compra->id
                );

                // 2. Aplicar el SoftDelete a la compra
                $compra->delete();
            });

            $this->alert('success', 'Compra eliminada correctamente y stock descontado del almacén.');
        } catch (\RuntimeException $e) {
            // Excepción de control (ej. si el movimiento ya fue registrado en el Kardex)
            $this->alert('warning', $e->getMessage());
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function restorePurchase(int $purchaseId): void
    {
        try {
            DB::transaction(function () use ($purchaseId) {
                $compra = Compra::onlyTrashed()->findOrFail($purchaseId);

                // 1. Restaurar la compra
                $compra->restore();

                // 2. Regenerar los movimientos de stock e incrementar el almacén
                app(StockService::class)->registrarMovimientosDesdeCompra($compra);
            });

            $this->alert('success', 'Compra restaurada correctamente y stock reingresado.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function exportToExcel(ExportToExcelService $service)
    {
        try {
            return $service->execute([
                'search' => $this->search,
                'documentType' => $this->tipoComprobante,
                'dateFrom' => $this->fechaDesde,
                'dateTo' => $this->fechaHasta,
                'supplierId' => $this->proveedorId,
                'showTrashed' => $this->verEliminadas,
                'sortBy' => $this->sortField,
                'sortDirection' => $this->sortDirection,
            ]);
        } catch (\Throwable $e) {
            session()->flash('error', 'Error al exportar a Excel: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.compras.lista-compras-component', [
            'compras' => $this->compras,
        ]);
    }
}