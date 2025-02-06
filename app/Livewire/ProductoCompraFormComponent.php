<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\Producto;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Models\SunatTabla6CodigoUnidadMedida;
use App\Models\TiendaComercial;
use App\Services\AlmacenServicio;
use App\Services\ProductoServicio;
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
    public $mensajeAlCambiarTipoKardex;
    public $compra;
    protected $listeners = ['agregarCompra','editarCompra'];
  
    public function mount()
    {
        $this->tabla10TipoComprobantePago = SunatTabla10TipoComprobantePago::all();
        $this->proveedores = TiendaComercial::orderBy('nombre')->get();
        $this->resetearValoresDefecto();
    }
    public function editarCompra($productoId,$compraId)
    {
        $this->resetearValoresDefecto();

        $this->productoId = $productoId;

        $this->producto = Producto::find($productoId);
        if (!$this->producto) {
            return $this->alert('error', 'El producto ya no existe.');
        }
        
        

        $this->compraId = $compraId;
        $compra = CompraProducto::find($this->compraId);
        
        
        if ($compra) {
            $this->compra = $compra;
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
        $this->validate([
            'fecha_compra' => 'required',
            'total' => 'required',
            'costo_por_kg' => 'required|numeric',  // Permite valores decimales con hasta 2 dígitos
            'tipoCompraSeleccionada'=>'required'
        ],[
            'tipoCompraSeleccionada.required' => 'El tipo de compra es obligatorio.',
            'fecha_compra.required' => 'La fecha de compra es obligatoria.',
            'total.required'=>'El total de la compra es obligatoria.',
            'costo_por_kg.required' => 'El costo por unidad debe ser un número válido.',
            'costo_por_kg.numeric' => 'El costo por kilogramo debe ser un número válido.',
        ]);

        try {

            if (!$this->productoId) {
                throw new Exception("Debe Seleccionar un Producto");
            }
            if ((int)$this->stock==0) {
                throw new Exception("El stock debe ser mayor a 0");
            }
            
            $data = [
                'producto_id' => $this->productoId,
                'fecha_compra' => $this->fecha_compra,
                'tienda_comercial_id' => (int)$this->tienda_comercial_id==0?null:$this->tienda_comercial_id,
                'serie' => mb_strtoupper($this->serie),
                'numero' => $this->numero,
                'costo_por_kg' => $this->total/$this->stock,
                'stock'=>$this->stock,
                'total'=>$this->total,
                'tipo_kardex'=>$this->tipoKardex,
                'tabla12_tipo_operacion'=>$this->tabla12TipoOperacion,
                'tipo_compra_codigo'=>$this->tipoCompraSeleccionada
            ];

            if ($this->compraId) {
                
                $compra = CompraProducto::find($this->compraId);
                if ($compra) {
                    ProductoServicio::actualizarCompra($compra, $data);                    
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                
                ProductoServicio::registrarCompraProducto([$data],false);
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
    public function updatedTipoKardex($valor){
        
        if($this->compraId && $this->compra){
            if($valor!=$this->compra->tipo_kardex){
                $this->mensajeAlCambiarTipoKardex = 'Ud está modificando el Kardex de esta compra, las salidas vinculadas a esta compra se van a desvincular';
            }else{
                $this->mensajeAlCambiarTipoKardex = '';
            }
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
            'tipoCompraSeleccionada',
            'compraId',
            'compra',
            'mensajeAlCambiarTipoKardex'
        ]);

        $this->resetErrorBag();
    }
    public function render()
    {
        return view('livewire.producto-compra-form-component');
    }
}
