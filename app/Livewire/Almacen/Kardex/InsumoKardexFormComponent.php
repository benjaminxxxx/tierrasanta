<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\InsKardex;
use App\Models\Producto;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Services\Almacen\Kardex\InsumoKardexServicio;
use App\Services\Producto\ProductoServicio;
use App\Traits\HandlesAlerts;
use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class InsumoKardexFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public $mostrarFormularioKardex = false;
    public $insumoKardexId;
    // ARRAY COMPUESTO
    public $kardex = [
        'producto_id' => null,
        'descripcion' => '',
        'codigo_existencia' => '',
        'anio' => '',
        'tipo' => '',
        'stock_inicial' => '',
        'costo_unitario' => '',
        'costo_total' => '',
        'tipo_compra_codigo_inicial' => '',
        'tiene_saldo_inicial',
        'serie_inicial' => '',
        'numero_inicial' => '',
    ];

    public $productos = [];
    // Nombre del producto precargado (x-select-dropdown no puede resolverlo solo desde el id)
    public string $productoEtiqueta = '';
    public $tabla10TipoComprobantePago = [];

    // Página de detalle: kardex que se podrá editar (se usa para precargar el nombre del producto)
    public ?int $kardexEditarId = null;

    /**
     * Edita un kardex activo: saldo inicial, comprobante, código, año, tipo, etc.
     * Después de guardar, el kardex queda desactualizado hasta volver a generar sus movimientos.
     */
    #[On('editarInsumoKardex')]
    public function editarInsumoKardex($kardexId)
    {
        $k = InsKardex::with('producto')->find($kardexId);
        if (!$k) {
            $this->alert('error', 'El kardex ya no existe.');
            return;
        }
        if ($k->estado === 'cerrado') {
            $this->alert('error', 'El kardex está cerrado. Reábrelo para poder editarlo.');
            return;
        }

        $this->resetForm();
        $this->insumoKardexId = $k->id;
        $this->kardex = [
            'producto_id' => $k->producto_id,
            'codigo_existencia' => $k->codigo_existencia,
            'anio' => $k->anio,
            'tipo' => $k->tipo,
            'stock_inicial' => (float) $k->stock_inicial,
            'costo_unitario' => (float) $k->costo_unitario,
            'costo_total' => (float) $k->costo_total,
            'tipo_compra_codigo_inicial' => $k->tipo_compra_codigo_inicial ?? '',
            'tiene_saldo_inicial' => (float) $k->stock_inicial > 0 || (float) $k->costo_total > 0,
            'serie_inicial' => $k->serie_inicial ?? '',
            'numero_inicial' => $k->numero_inicial ?? '',
        ];
        $this->productoEtiqueta = $this->etiquetaProducto($k->producto);
        // El buscador de producto (wire:ignore) solo lee la etiqueta al iniciar: se le envía
        // la del kardex elegido (listado y reporte abren kardex distintos con el mismo modal)
        $this->dispatch('selectDropdownEtiqueta', model: 'kardex.producto_id', etiqueta: $this->productoEtiqueta);
        $this->mostrarFormularioKardex = true;
    }

    /**
     * Código libre sugerido para el producto/año/tipo del formulario (según su grupo operativo).
     */
    public function usarCodigoSugerido(): void
    {
        $sugerido = $this->codigoSugerido();
        if ($sugerido) {
            $this->kardex['codigo_existencia'] = $sugerido;
            $this->resetErrorBag('kardex.codigo_existencia');
        }
    }

    private function codigoSugerido(): ?string
    {
        $k = $this->kardex;
        if (empty($k['producto_id']) || empty($k['anio']) || !in_array($k['tipo'] ?? null, ['blanco', 'negro'], true)) {
            return null;
        }
        return app(InsumoKardexServicio::class)
            ->sugerirCodigo((int) $k['producto_id'], (int) $k['anio'], $k['tipo'], $this->insumoKardexId);
    }

    private function etiquetaProducto($producto): string
    {
        return $producto
            ? $producto->nombre_comercial . ($producto->ingrediente_activo ? " ({$producto->ingrediente_activo})" : '')
            : '';
    }

    #[On('nuevoInsumoKardex')]
    public function nuevoInsumoKardex($productoId = null)
    {

        $this->insumoKardexId = null;
        $this->productoEtiqueta = '';
        $this->resetForm();
        $this->kardex['producto_id'] = $productoId;
        if ($productoId) {
            $this->productoEtiqueta = $this->etiquetaProducto(Producto::withTrashed()->find($productoId));
        }
        $this->dispatch('selectDropdownEtiqueta', model: 'kardex.producto_id', etiqueta: $this->productoEtiqueta);
        $this->dispatch('resetearCalculos');
        $this->mostrarFormularioKardex = true;
    }
    public function getProductos(string $search): array
    {
        return app(ProductoServicio::class)->buscar($search);
    }

    public function mount()
    {
        $this->kardex = [
            'producto_id' => null,
            'codigo_existencia' => '',
            'anio' => '',
            'tipo' => '',
            'stock_inicial' => '',
            'costo_unitario' => '',
            'costo_total' => '',
            'tipo_compra_codigo_inicial' => '',
            'tiene_saldo_inicial' => false,
            'serie_inicial' => '',
            'numero_inicial' => '',
        ];
        $this->tabla10TipoComprobantePago = SunatTabla10TipoComprobantePago::all();
        //dd(5);
        // Enlace "Crear kardex" de Tareas pendientes: ?crear=1&producto_id=&tipo=&anio=
        // Detalle del kardex: el buscador (wire:ignore) solo lee la etiqueta al iniciar
        if ($this->kardexEditarId) {
            $this->productoEtiqueta = $this->etiquetaProducto(InsKardex::with('producto')->find($this->kardexEditarId)?->producto);
        }

        if (request()->boolean('crear') && request()->filled('producto_id')) {
            //dd(request('producto_id'));
            $this->prellenarDesdeEnlace(
                (int) request('producto_id'),
                request('tipo'),
                request('anio') ? (int) request('anio') : null
            );
        }
        /*
        $this->productos = Producto::orderBy('nombre_comercial')->get()->map(function ($producto) {
            return [
                'id' => $producto->id,
                'name' => $producto->nombre_comercial
            ];
        })
            ->toArray();*/
    }

    /**
     * Abre el formulario con producto, tipo y año ya elegidos. Queda por completar el saldo
     * inicial y, si no se pudo sugerir, el código de existencia.
     */
    private function prellenarDesdeEnlace(int $productoId, ?string $tipo, ?int $anio): void
    {
        $producto = Producto::withTrashed()->find($productoId);
        if (!$producto) {
            return;
        }

        $this->kardex['producto_id'] = $producto->id;
        $this->kardex['tipo'] = in_array($tipo, ['blanco', 'negro'], true) ? $tipo : '';
        $this->kardex['anio'] = $anio ?? now()->year;
        // El código de existencia suele repetirse entre años/tipos del mismo producto
        $this->kardex['codigo_existencia'] = InsKardex::where('producto_id', $producto->id)
            ->whereNotNull('codigo_existencia')
            ->orderByDesc('anio')
            ->value('codigo_existencia') ?? '';
        $this->productoEtiqueta = $this->etiquetaProducto($producto);
        $this->mostrarFormularioKardex = true;
    }

    public function guardarKardex()
    {
        try {
            if (!($this->kardex['tiene_saldo_inicial'] ?? false)) {
                $this->kardex['stock_inicial'] = 0;
                $this->kardex['costo_total'] = 0;
            }

            $this->resetErrorBag();
            $kardex = app(InsumoKardexServicio::class)->guardarInsumoKardex($this->kardex, $this->insumoKardexId);
            $this->dispatch('insumoKardexRefrescar', kardexId: $kardex->id);
            // Para pantallas que muestran el kardex de forma resumida (ej. reporte general)
            $this->dispatch('kardexGuardado', kardexId: $kardex->id, editado: (bool) $this->insumoKardexId);
            $this->mostrarFormularioKardex = false;
            $textoCreado = $this->insumoKardexId ? 'actualizado' : 'creado';
            $this->alert('success', "Kardex {$textoCreado} correctamente.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los campos del formulario están bajo "kardex."
            foreach ($e->errors() as $campo => $mensajes) {
                $this->addError("kardex.{$campo}", $mensajes[0]);
            }
            $this->errorAlert($e);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function resetForm()
    {
        $this->resetErrorBag();

        $this->kardex = [
            'producto_id' => null,
            'codigo_existencia' => '',
            'anio' => '',
            'tipo' => '',
            'stock_inicial' => '',
            'costo_unitario' => '',
            'costo_total' => '',
            'tipo_compra_codigo_inicial' => '',
            'serie_inicial' => '',
            'numero_inicial' => '',
        ];
    }

    public function render()
    {
        return view('livewire.almacen.kardex.insumo-kardex-form-component', [
            'codigoSugerido' => $this->mostrarFormularioKardex ? $this->codigoSugerido() : null,
        ]);
    }
}
