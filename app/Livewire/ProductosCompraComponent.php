<?php

namespace App\Livewire;

use App\Models\CompraProducto;
use App\Models\TiendaComercial;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosCompraComponent extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $productoId;
    public $compraId;
    public $mostrarFormulario = false;
    public $proveedores;

    public $tienda_comercial_id;
    public $fecha_compra;
    public $costo_por_kg;
    public $factura;

    public $sortField = 'fecha_compra'; 
    public $sortDirection = 'desc';

    public $compraIdEliminar;

    public $modo;

    protected $listeners = ['VerComprasProducto','eliminacionConfirmadaCompra'];
    protected function rules()
    {
        return [
            'tienda_comercial_id' => 'required',
            'fecha_compra' => 'required',
            'costo_por_kg' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',  // Permite valores decimales con hasta 2 dígitos
            'factura' => 'nullable'
        ];
    }

    protected $messages = [
        'tienda_comercial_id.required' => 'La tienda comercial es obligatoria.',
        'fecha_compra.required' => 'La fecha de compra es obligatoria.',
        'costo_por_kg.required' => 'El costo por kilogramo/Listro debe ser un número válido.',
        'costo_por_kg.numeric' => 'El costo por kilogramo debe ser un número válido.',
        'costo_por_kg.regex' => 'El costo por kilogramo debe ser un valor decimal con hasta 2 dígitos después del punto.',
    ];

    public function VerComprasProducto($id)
    {
        $this->productoId = $id;
        $this->mostrarFormulario = true;
        $this->sortField = 'fecha_compra'; 
        $this->sortDirection = 'desc';
    }
    public function mount()
    {
        $this->proveedores = TiendaComercial::orderBy('nombre')->get();
        $this->resetearValoresDefecto();
    }
    public function enable($id)
    {
        $compra = CompraProducto::find($id);
        if ($compra) {
            $compra->estado = '1';
            $compra->save();
        }
    }
    public function disable($id)
    {
        $compra = CompraProducto::find($id);
        if ($compra) {
            $compra->estado = '0';
            $compra->save();
        }
    }
    public function editarCompra($id){
        $this->compraId = $id;
        $compra = CompraProducto::find($this->compraId);
        if($compra){
            $this->tienda_comercial_id = $compra->tienda_comercial_id;
            $this->fecha_compra = $compra->fecha_compra;
            $this->factura = $compra->factura;
            $this->costo_por_kg = $compra->costo_por_kg;
        }
    }
    public function agregarCompra()
    {

        $this->validate();

        try {

            if(!$this->productoId){
                throw new Exception("Debe Seleccionar un Producto");
            }

            $data = [
                'producto_id' => $this->productoId,
                'tienda_comercial_id' => $this->tienda_comercial_id,
                'fecha_compra' => $this->fecha_compra,
                'factura' => mb_strtoupper($this->factura),
                'costo_por_kg'=>$this->costo_por_kg
            ];

            if ($this->compraId) {
                $compra = CompraProducto::find($this->compraId);
                if ($compra) {
                    $compra->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['estado']='1';
                CompraProducto::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar
            $this->reset([
                'tienda_comercial_id',
                'factura',
                'costo_por_kg'
            ]);

            $this->dispatch('actualizarAlmacen');
            $this->resetearValoresDefecto();
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
   
    public function resetearValoresDefecto(){
        $this->fecha_compra = Carbon::now()->format('Y-m-d');
    }
    public function closeForm(){
        $this->mostrarFormulario = false;
    }
    public function continuar(){
        $this->dispatch("continuar",$this->productoId);
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
    public function confirmarEliminacion($id)
    {
        $this->compraIdEliminar = $id;

        $this->alert('question', '¿Está seguro que desea eliminar la compra?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmadaCompra',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function eliminacionConfirmadaCompra()
    {
        if ($this->compraIdEliminar) {
            $compra = CompraProducto::find($this->compraIdEliminar);
            if ($compra) {
                $compra->delete();
                $this->compraIdEliminar = null;
                $this->resetPage();
                $this->alert('success', 'Compra Eliminada');
            }
        }
    }
    public function render()
    {
        $compras = null;
        if($this->productoId){
            $compras = CompraProducto::where('producto_id',$this->productoId)->orderBy($this->sortField, $this->sortDirection)->paginate(5);
        }
        return view('livewire.productos-compra-component',[
            'compras'=>$compras
        ]);
    }
}
