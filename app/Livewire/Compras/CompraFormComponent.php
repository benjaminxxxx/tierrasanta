<?php
namespace App\Livewire\Compras;

use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Services\Almacen\CompraService;
use App\Traits\HandlesAlerts;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva compra')]
class CompraFormComponent extends Component
{
    use HandlesAlerts, LivewireAlert;

    public bool $mostrarModal = false;
    public ?int $compraId = null;
    public string $modo = 'crear';

    public ?int $proveedorId = null;
    public ?int $productoSeleccionadoId = null;

    public string $almacenId = '';
    public string $moneda = 'PEN';
    public string $tipoCambio = '1';
    public ?string $tipo_comprobante_codigo = null;
    public string $serie = '';
    public string $numero = '';
    public string $fechaEmision = '';
    public string $fechaVencimiento = '';
    public string $formaPago = 'contado';
    public string $notas = '';

    // Propiedades entangleadas con Alpine
    public float $total = 0;
    public float $subtotal = 0;
    public float $igvTotal = 0;
    public array $detalles = [];

    public array $proveedores = [];
    public $almacenes = [];
    public array $productos = [];
    public $tipoComprobantes = [];
    public string $tipoKardex = 'blanco';

    protected $listeners = ['abrirFormularioNuevaCompra', 'abrirFormularioEditarCompra'];

    public function mount(): void
    {
        $this->obtenerProveedores();
        $this->obtenerAlmacenes();
        $this->obtenerProductos();
        $this->tipoComprobantes = SunatTabla10TipoComprobantePago::all();

        $this->fechaEmision = now()->format('Y-m-d');


    }
    /**
     * Limpia todas las propiedades del formulario a sus valores por defecto.
     */
    public function resetearFormulario(): void
    {
        $this->reset([
            'compraId',
            'proveedorId',
            'almacenId',
            'moneda',
            'tipoCambio',
            'tipo_comprobante_codigo',
            'serie',
            'numero',
            'fechaEmision',
            'fechaVencimiento',
            'formaPago',
            'notas',
            'tipoKardex',
        ]);

        $this->detalles = [];
        $this->obtenerAlmacenes();
        $this->resetValidation(); // Limpia los mensajes de error de validación previo si existen
    }

    public function abrirFormularioNuevaCompra(): void
    {
        $this->resetearFormulario();
        $this->modo = 'crear';

        // Si tienes valores por defecto para una nueva compra, los asignas aquí:
        $this->fechaEmision = now()->format('Y-m-d');
        $this->moneda = 'PEN'; // O la moneda por defecto de tu sistema

        $this->abrirModal();
    }

    public function abrirFormularioEditarCompra($compraId): void
    {
        $this->resetearFormulario();


        $compra = Compra::with(['detalles.producto.presentaciones', 'detalles.presentacion'])->find($compraId);

        if ($compra && $compra->exists) {
            $this->tipoKardex = $compra->tipo_kardex ?? 'blanco';
            $this->modo = 'editar';
            $this->compraId = $compra->id;
            $this->proveedorId = $compra->proveedor_id;
            $this->almacenId = (string) $compra->almacen_id;
            $this->moneda = $compra->moneda;
            $this->tipoCambio = (string) $compra->tipo_cambio;
            $this->tipo_comprobante_codigo = $compra->tipo_comprobante_codigo;
            $this->serie = $compra->serie ?? '';
            $this->numero = $compra->numero ?? '';
            $this->fechaEmision = $compra->fecha_emision?->format('Y-m-d') ?? '';
            $this->fechaVencimiento = $compra->fecha_vencimiento?->format('Y-m-d') ?? '';
            $this->formaPago = $compra->forma_pago;
            $this->notas = $compra->notas ?? '';

            foreach ($compra->detalles as $detalle) {
                $this->detalles[] = [
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto?->marca
                        ? "{$detalle->producto->nombre} ({$detalle->producto->marca})"
                        : $detalle->producto?->nombre ?? 'Producto no encontrado',
                    'presentacion_id' => $detalle->presentacion_id ?: '',
                    'factor_conversion' => $detalle->presentacion?->factor_conversion ?? 1,
                    'cantidad' => (string) $detalle->cantidad,
                    'costo_unitario' => (string) $detalle->costo_unitario,
                    'porcentaje_descuento' => (string) $detalle->porcentaje_descuento,
                    'porcentaje_igv' => (string) $detalle->porcentaje_igv,
                    'presentaciones' => $detalle->producto?->presentaciones->map(fn($p) => [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'factor_conversion' => $p->factor_conversion,
                    ])->toArray() ?? [],
                ];
            }

            $this->abrirModal();
        }
    }
    public function abrirModal(): void
    {
        $this->mostrarModal = true;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
    }

    public function obtenerProveedores(): void
    {
        $this->proveedores = Proveedor::get()->map(fn($proveedor) => [
            'id' => $proveedor->id,
            'name' => $proveedor->razon_social ?? $proveedor->nombre_comercial,
        ])->toArray();
    }

    public function obtenerProductos(): void
    {
        $this->productos = Producto::get()->map(fn($producto) => [
            'id' => $producto->id,
            'name' => $producto->nombre_completo,
        ])->toArray();
    }

    public function obtenerAlmacenes(): void
    {
        $this->almacenes = Almacen::orderBy('nombre')->get();
        if ($this->almacenes->count() > 0) {
            $this->almacenId = $this->almacenes->first()->id;
        }
    }

    public function updatedProductoSeleccionadoId($valor): void
    {
        if (!$valor)
            return;

        $producto = Producto::with('presentaciones')->find($valor);
        if (!$producto)
            return;

        $presentacionPorDefecto = $producto->presentaciones->firstWhere('es_defecto_compra', true)
            ?? $producto->presentaciones->first();

        $this->detalles[] = [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre_completo,
            'presentacion_id' => $presentacionPorDefecto?->id ?? '',
            'factor_conversion' => $presentacionPorDefecto?->factor_conversion ?? 1,
            'cantidad' => '1',
            'costo_unitario' => '',
            'porcentaje_descuento' => '0',
            'porcentaje_igv' => '18',
            'presentaciones' => $producto->presentaciones->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'factor_conversion' => $p->factor_conversion,
            ])->toArray(),
        ];

        $this->productoSeleccionadoId = null;
    }

    public function guardarCompraInsumo(CompraService $servicio): void
    {
        $this->validate([
            'proveedorId' => ['required', 'exists:proveedores,id'],
            'almacenId' => ['required', 'exists:almacenes,id'],
            'fechaEmision' => ['required', 'date'],
            'tipo_comprobante_codigo' => ['required', 'exists:sunat_tabla10_tipo_comprobantes_pago,codigo'],
            'serie' => ['nullable', 'string', 'max:10', 'required_with:numero'],
            'numero' => [
                'nullable',
                'string',
                'max:20',
                'required_with:serie',
                Rule::unique('compras', 'numero')
                    ->where(function ($query) {
                        return $query
                            ->where('proveedor_id', $this->proveedorId)
                            ->where('serie', $this->serie);
                    })
                    ->ignore($this->compraId),
            ],
            'tipoKardex' => ['required', 'in:blanco,negro'],
        ], [
            'numero.unique' => 'Ya existe una compra registrada con ese proveedor, serie y número de comprobante.',
            'serie.required_with' => 'La serie es obligatoria si ingresas número.',
            'numero.required_with' => 'El número es obligatorio si ingresas serie.',
        ]);

        if (empty($this->detalles)) {
            $this->errorAlert('Agrega al menos un producto a la compra.');
            return;
        }

        foreach ($this->detalles as &$item) {
            if (!filled($item['costo_unitario']) || (float) $item['costo_unitario'] <= 0) {
                $this->errorAlert('Todos los productos deben tener un precio unitario válido.');
                return;
            }
            $item['presentacion_id'] = $item['presentacion_id'] == '' ? null : $item['presentacion_id'];
        }

        $cabecera = [
            'proveedor_id' => $this->proveedorId,
            'almacen_id' => $this->almacenId,
            'moneda' => $this->moneda,
            'tipo_cambio' => $this->moneda === 'USD' ? $this->tipoCambio : 1,
            'tipo_comprobante_codigo' => $this->tipo_comprobante_codigo,
            'serie' => $this->serie ?: null,
            'numero' => $this->numero ?: null,
            'fecha_emision' => $this->fechaEmision,
            'fecha_vencimiento' => $this->fechaVencimiento ?: null,
            'forma_pago' => $this->formaPago,
            'notas' => $this->notas,
            'tipo_kardex' => $this->tipoKardex,
        ];

        try {

            if ($this->modo === 'editar') {
                $compra = Compra::findOrFail($this->compraId);
                $servicio->actualizar($compra, $cabecera, $this->detalles);

                $this->dispatch('nuevaCompraActualizada');
            } else {
                $servicio->crear($cabecera, $this->detalles);

                $this->dispatch('nuevaCompraRegistrada');
            }

            $this->mostrarModal = false;
            $this->alert('success', 'Compra registrada');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.compras.compra-form-component');
    }
}