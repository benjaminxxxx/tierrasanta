<?php

namespace App\Livewire\GestionAlmacen;
use App\Models\CompraProducto;
use App\Models\Producto;
use App\Services\AuditoriaServicio;
use App\Services\Insumo\CompraInsumoServicio;
use App\Services\ProductoServicio;
use App\Traits\ListasComunes\HstListas;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Session;

class CompraProductosComponent extends Component
{
    use HstListas, LivewireAlert;
    public $compras = [];
    public array $listaProductos = [];
    public array $listaProveedores = [];
    public array $listaTipoDocumentos = [];
    public array $filasModificadas = [];
    public $filtroAnio;
    public $filtroMes;
    public $filtroDia;
    public $filtroTipoKardex;
    public $filtroTipoComprobante;
    public $busquedaGeneral;
    protected $sessionMap = [
        'filtroAnio' => 'anio',
        'filtroMes' => 'mes',
        'filtroDia' => 'dia',
        'filtroTipoKardex' => 'tipo_kardex',
        'filtroTipoComprobante' => 'tipo_comprobante',
        'busquedaGeneral' => 'busqueda',
    ];
    public bool $mostrarDetalleCompras = false;
    public array $comprasDetalle = [];       // filas a mostrar en el modal de detalle
    public array $idsParaEliminar = [];      // ids confirmados para eliminar
    //protected $listeners = ['ejecutarEliminarSeleccionados'];
    public function mount($producto_id = null)
    {
        $this->cargarFiltros($producto_id);
        $this->obtenerCompras();
        $this->cargarListas();
    }
    // ── Ver detalle completo ──────────────────────────────────────────────────────
    public function verInformacionSeleccionados(array $ids): void
    {
        $ids = array_filter($ids, fn($id) => !is_null($id) && $id !== '');

        if (empty($ids)) {
            $this->alert('warning', 'Selecciona al menos un registro guardado.');
            return;
        }

        $this->comprasDetalle = CompraProducto::with(['producto', 'proveedor', 'creador', 'editor', 'eliminador'])
            ->whereIn('id', $ids)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'producto' => $c->producto?->nombre_comercial ?? '—',
                'proveedor' => $c->proveedor?->nombre ?? '—',
                'fecha_compra' => $c->fecha_compra,
                'serie' => $c->serie,
                'numero' => $c->numero,
                'stock' => $c->stock,
                'total' => $c->total,
                'costo_por_unidad' => $c->costo_por_unidad,
                'tipo_kardex' => $c->tipo_kardex,
                'tipo_compra_codigo' => $c->tipo_compra_codigo,
                'creado_por' => $c->creador?->name,
                'editado_por' => $c->editor?->name,
                'eliminado_por' => $c->eliminador?->name,
                'created_at' => $c->created_at?->format('d/m/Y H:i'),
                'updated_at' => $c->updated_at?->format('d/m/Y H:i'),
                'deleted_at' => $c->deleted_at?->format('d/m/Y H:i'),
                // ✅ Historial de auditoría
                'auditoria' => AuditoriaServicio::getAuditoria(CompraProducto::class, $c->id),
            ])
            ->toArray();

        $this->mostrarDetalleCompras = true;
    }
    // ── Eliminar seleccionados ────────────────────────────────────────────────────
    public function eliminarSeleccionados(array $ids): void
    {
        $ids = array_filter($ids, fn($id) => !is_null($id) && $id !== '');

        if (empty($ids)) {
            $this->alert('warning', 'Selecciona al menos un registro guardado.');
            return;
        }

        $this->idsParaEliminar = array_values($ids);
        $count = count($this->idsParaEliminar);

        $this->confirm("¿Eliminar {$count} " . ($count === 1 ? 'registro' : 'registros') . " seleccionado(s)?", [
            'onConfirmed' => 'ejecutarEliminarSeleccionados',
        ]);
    }

    #[On('ejecutarEliminarSeleccionados')]
    public function ejecutarEliminarSeleccionados(): void
    {
        try {
            $eliminados = 0;

            DB::transaction(function () use (&$eliminados) {
                foreach ($this->idsParaEliminar as $id) {
                    $compra = CompraProducto::find($id);
                    if ($compra) {
                        CompraInsumoServicio::limpiarSalidasAsociadas($compra, $compra->tipo_kardex);

                        $compra->delete(); // soft delete → booted() setea eliminado_por
                        $eliminados++;
                    }
                }
            });

            $this->idsParaEliminar = [];
            $this->alert('success', "{$eliminados} registro(s) eliminado(s) correctamente.");
            $this->obtenerCompras();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function cargarFiltros($producto_id = null)
    {
        $this->filtroAnio = Session::get('anio', now()->year);
        $this->filtroMes = Session::get('mes', now()->month);
        $this->filtroDia = Session::get('dia', null);

        $this->filtroTipoKardex = Session::get('tipo_kardex', null);
        $this->filtroTipoComprobante = Session::get('tipo_comprobante', null);
        if ($producto_id) {
            $producto = Producto::find($producto_id);
            if ($producto) {
                $this->busquedaGeneral = $producto->nombre_comercial;
            }
        } else {
            $this->busquedaGeneral = Session::get('busqueda', null);
        }

    }
    public function updated($property, $value)
    {
        if (!isset($this->sessionMap[$property])) {
            return;
        }

        Session::put($this->sessionMap[$property], $value);

        if ($property === 'filtroAnio' || $property === 'filtroMes') {
            $this->filtroDia = null;
            Session::forget('dia');
        }

        $this->obtenerCompras();
    }
    public function cargarListas()
    {
        $this->listaProductos = $this->cargarListaHstProductos();
        $this->listaProveedores = $this->cargarListaHstProveedores();
        $this->listaTipoDocumentos = $this->cargarListaHstTipoDocumentos();
    }
    public function obtenerCompras()
    {
        $query = CompraProducto::query()
            ->with(['producto', 'proveedor']); // asumiendo relaciones

        // 🔹 Año
        if ($this->filtroAnio) {
            $query->whereYear('fecha_compra', $this->filtroAnio);
        }

        // 🔹 Mes
        if ($this->filtroMes) {
            $query->whereMonth('fecha_compra', $this->filtroMes);
        }

        // 🔹 Día
        if ($this->filtroDia) {
            $query->whereDay('fecha_compra', $this->filtroDia);
        }

        // 🔹 Tipo Kardex
        if ($this->filtroTipoKardex) {
            $query->where('tipo_kardex', $this->filtroTipoKardex);
        }



        // 🔹 Tipo comprobante
        if ($this->filtroTipoComprobante) {
            $query->where('tipo_compra_codigo', $this->filtroTipoComprobante);
        }

        // 🔹 Búsqueda general (PRO)
        if ($this->busquedaGeneral) {
            $search = $this->busquedaGeneral;

            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%$search%")
                    ->orWhere('numero', 'like', "%$search%")
                    ->orWhereHas('producto', fn($q) =>
                        $q->where('nombre_comercial', 'like', "%$search%"))
                    ->orWhereHas('proveedor', fn($q) =>
                        $q->where('nombre', 'like', "%$search%"));
            });
        }

        $this->compras = $query->get();

        $this->dispatch('actualizarCompraProductos', data: $this->compras);
    }
    public function guardarCompraInsumos(array $data)
    {
        try {

            $resultados = CompraInsumoServicio::guardarCompras($data);

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->filasModificadas = [];
            $this->obtenerCompras();
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-almacen.compra-productos-component');
    }
}