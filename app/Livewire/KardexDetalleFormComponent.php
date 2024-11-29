<?php

namespace App\Livewire;

use App\Models\Kardex;
use App\Models\KardexProducto;
use App\Models\Producto;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexDetalleFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $kardexProductoId;
    public $kardexId;
    public $productoId;
    public $stockInicial;
    public $costoUnitario;
    public $metodoValuacion;
    public $costoTotal;
    public $kardex;
    public $codigo_existencia;
    public $productosDisponibles = [];
    protected $listeners = ['crearKardexProducto', 'editarKardexProducto'];
    protected $rules = [
        "productoId" => "required",
        "stockInicial" => "required",
        "costoUnitario" => "required",
        "metodoValuacion" => "required",
        "codigo_existencia"=>"required"
    ];
    protected $messages = [
        "productoId.required" => "El producto es requerido",
        "codigo_existencia.required" => "El código de existencia es requerido",
        "stockInicial.required" => "El stock inicial es requerido",
        "costoTotal.required" => "El costo total es requerido",
        'costoUnitario.required'=>'El costo unitario es obligatorio'
    ];
    public function mount()
    {
        $this->metodoValuacion = 'promedio';
        $this->kardex = Kardex::find($this->kardexId);
        $this->productosDisponibles = Producto::orderBy('nombre_comercial')->get();
    }
    public function crearKardexProducto()
    {
        $this->mostrarFormulario = true;
        $this->resetErrorBag();
        $this->resetForm();
    }
    public function editarKardexProducto($kardexProductoId)
    {
        $this->resetForm();
        $this->kardexProductoId = $kardexProductoId;
        $kardexProducto = KardexProducto::find($this->kardexProductoId);
        if ($kardexProducto) {
            $this->productoId = $kardexProducto->producto_id;
            $this->codigo_existencia = $kardexProducto->codigo_existencia;
            $this->stockInicial = $kardexProducto->stock_inicial;
            $this->costoUnitario = $kardexProducto->costo_unitario;
            $this->costoTotal = $kardexProducto->costo_total;
            $this->metodoValuacion = $kardexProducto->metodo_valuacion;
            $this->mostrarFormulario = true;
        }

    }
    public function storeKardexProductoForm()
    {

        $this->validate();

        try {

            $this->costoTotal = $this->stockInicial * $this->costoUnitario;

            $data = [
                'kardex_id' => $this->kardexId,
                'producto_id' => $this->productoId,
                'stock_inicial' => $this->stockInicial,
                'costo_unitario' => $this->costoUnitario,
                'costo_total' => $this->costoTotal,
                'metodo_valuacion' => $this->metodoValuacion,
                'codigo_existencia'=>$this->codigo_existencia,
            ];

            if ($this->kardexProductoId) {
                KardexProducto::find($this->kardexProductoId)->update($data);
            } else {
                $kardexExiste = KardexProducto::where('kardex_id',$this->kardexId)
                ->where('producto_id',$this->productoId)
                ->exists();
                if($kardexExiste){
                    throw new Exception("El producto ya existe en este Kardex");                    
                }
                KardexProducto::create($data);
            }

            $this->dispatch("kardexProductoRegistrado");
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->alert("success", "Registro de Kardex exitoso");
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['costoUnitario', 'stockInicial', 'kardexProductoId','codigo_existencia']);
    }
    public function render()
    {
        return view('livewire.kardex-detalle-form-component');
    }
}
