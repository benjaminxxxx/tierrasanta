<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\Producto;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Models\SunatTabla6CodigoUnidadMedida;
use App\Models\TiendaComercial;
use App\Services\AlmacenServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;
use Illuminate\Database\QueryException;

class ProductoCompraFormComponent extends Component
{
    use LivewireAlert;

    public $fecha_compra;
    public $costo_por_kg;
    public $tienda_comercial_id;
    public $stock;
    public $total;
    public $proveedores;
    public $serie;
    public $numero;
    public $tipoKardex = 'blanco';
    public $mostrarFormulario = false;
    public $productoId;
    public $producto;
    public $compraId;
    public $tabla10TipoComprobantePago;
    public $tabla12TipoOperacion = 2;
    public $tipoCompraSeleccionada;
    protected $listeners = ['agregarCompra','editarCompra'];
    protected function rules()
    {
        return [
            'tienda_comercial_id' => 'required',
            'fecha_compra' => 'required',
            'total' => 'required',
            'costo_por_kg' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',  // Permite valores decimales con hasta 2 dígitos
           'tipoCompraSeleccionada'=>'required'
        ];
    }

    protected $messages = [
        'tienda_comercial_id.required' => 'La tienda comercial es obligatoria.',
        'tipoCompraSeleccionada.required' => 'El tipo de compra es obligatorio.',
        'fecha_compra.required' => 'La fecha de compra es obligatoria.',
        'total.required'=>'El total de la compra es obligatoria.',
        'costo_por_kg.required' => 'El costo por unidad debe ser un número válido.',
        'costo_por_kg.numeric' => 'El costo por kilogramo debe ser un número válido.',
        'costo_por_kg.regex' => 'El costo por kilogramo debe ser un valor decimal con hasta 2 dígitos después del punto.',
    ];
    public function mount()
    {
        $this->tabla10TipoComprobantePago = SunatTabla10TipoComprobantePago::all();
        $this->proveedores = TiendaComercial::orderBy('nombre')->get();
        $this->resetearValoresDefecto();
    }
    public function editarCompra($productoId,$compraId)
    {
        $this->productoId = $productoId;

        $this->producto = Producto::find($productoId);
        if (!$this->producto) {
            return $this->alert('error', 'El producto ya no existe.');
        }

        $this->compraId = $compraId;
        $compra = CompraProducto::find($this->compraId);
        
        $this->resetearValoresDefecto();
        if ($compra) {
            $this->tienda_comercial_id = $compra->tienda_comercial_id;
            $this->fecha_compra = $compra->fecha_compra;
            $this->serie = $compra->serie;
            $this->numero = $compra->numero;
            $this->stock = $compra->stock;
            $this->total = $compra->total;
            $this->costo_por_kg = $compra->costo_por_kg;
            $this->mostrarFormulario = true;
            $this->tipoKardex = $compra->tipo_kardex;
            $this->tabla12TipoOperacion = $compra->tabla12_tipo_operacion;
            $this->tipoCompraSeleccionada = $compra->tipo_compra_codigo;

        }
    }
    public function agregarCompra($productoId)
    {
        $this->productoId = $productoId;

        $this->producto = Producto::find($productoId);
        if (!$this->producto) {
            return $this->alert('error', 'El producto ya no existe.');
        }

        $this->mostrarFormulario = true;
        $this->resetearValoresDefecto();
    }
    public function store()
    {
        $this->validate();

        try {

            if (!$this->productoId) {
                throw new Exception("Debe Seleccionar un Producto");
            }

            if (round($this->total,2) != round($this->stock * $this->costo_por_kg,2)) {
                throw new Exception("El total debe ser igual al resultado de stock multiplicado por el costo por unidad: " . $this->total . " != " . ($this->stock * $this->costo_por_kg));
            }

            
            $data = [
                'producto_id' => $this->productoId,
                'tienda_comercial_id' => $this->tienda_comercial_id,
                'fecha_compra' => $this->fecha_compra,
                'sere' => mb_strtoupper($this->serie),
                'numero' => $this->numero,
                'costo_por_kg' => $this->costo_por_kg,
                'stock'=>$this->stock,
                'total'=>$this->total,
                'tipo_kardex'=>$this->tipoKardex,
                'tabla12_tipo_operacion'=>$this->tabla12TipoOperacion,
                'tipo_compra_codigo'=>$this->tipoCompraSeleccionada
            ];

            if ($this->compraId) {
                $compra = CompraProducto::find($this->compraId);
                if ($compra) {
                    $compra->update($data);
                    AlmacenServicio::eliminarRegistrosPosteriores($compra,$this->fecha_compra);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['estado'] = '1';
                CompraProducto::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar


            $this->dispatch('actualizarAlmacen');
            $this->resetearValoresDefecto();
            $this->mostrarFormulario = false;
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function resetearValoresDefecto()
    {
        $this->fecha_compra = Carbon::now()->format('Y-m-d');
        $this->tipoKardex = 'blanco';
        $this->reset([
            'tienda_comercial_id',
            'serie',
            'numero',
            'costo_por_kg',
            'stock',
            'total',
            'tipoCompraSeleccionada'
        ]);

        $this->resetErrorBag();
    }
    public function render()
    {
        return view('livewire.producto-compra-form-component');
    }
}
