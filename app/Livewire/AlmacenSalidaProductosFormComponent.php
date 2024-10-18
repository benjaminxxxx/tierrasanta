<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\CompraProducto;
use App\Models\Producto;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaProductosFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $productos;
    public $nombre_comercial;
    public $informacion = [];
    public $camposAgregados = [];
    public $campos;
    public $step = 1;
    public $fecha_salida;
    public $mes;
    public $anio;
    protected $listeners = ['nuevoRegistro'];
    public function mount()
    {
        $this->obtenerFechaSalida();
        $this->campos = Campo::orderBy('orden')->get();
    }
    public function obtenerFechaSalida()
    {
        // Obtener el mes y año actuales
        $mesActual = Carbon::now()->month;
        $anioActual = Carbon::now()->year;
    
        if ($this->mes && $this->anio) {
            // Si el mes y el año son iguales al presente, usar la fecha actual
            if ($this->mes == $mesActual && $this->anio == $anioActual) {
                $this->fecha_salida = Carbon::now()->format('Y-m-d');
            } else {
                // De lo contrario, crear una fecha con el día 1
                $this->fecha_salida = Carbon::create($this->anio, $this->mes, 1)->format('Y-m-d');
            }
        } else {
            // Si no hay mes ni año, usar la fecha actual
            $this->fecha_salida = Carbon::now()->format('Y-m-d');
        }
    }
    public function toggleCampo($campoNombre)
    {
        if (in_array($campoNombre, $this->camposAgregados)) {
            // Eliminar el campo si ya está seleccionado
            $this->camposAgregados = array_diff($this->camposAgregados, [$campoNombre]);
        } else {
            // Agregar el campo si no está seleccionado
            $this->camposAgregados[] = $campoNombre;
        }
    }
    public function nuevoRegistro($mes,$anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->resetCampos();
        $this->obtenerFechaSalida();
        $this->mostrarFormulario = true;
    }
    public function updatedNombreComercial()
    {
        // Hacer la búsqueda en base a lo que se escribe en el campo nombre_comercial
        if (strlen($this->nombre_comercial) > 2) { // Solo buscar si tiene más de 2 caracteres
            $this->productos = Producto::where('nombre_comercial', 'like', '%' . $this->nombre_comercial . '%')
                ->orWhere('ingrediente_activo', 'like', '%' . $this->nombre_comercial . '%')
                ->take(5) // Limitar los resultados a 5 para no saturar la lista flotante
                ->get();
        } else {
            $this->productos = [];
        }
    }
    public function seleccionarProducto($productoId)
    {
        $producto = Producto::find($productoId);
        if ($producto) {

            $this->informacion['producto'] = $producto;
            $this->elegirCampos();
        }
    }
    
    public function retroceder()
    {
        $this->informacion = [];
        $this->step = 1;
    }
    public function store()
    {
        try {
            if(!isset($this->informacion['producto'])){
                return $this->alert('error','No ha seleccionado el producto');
            }
            if($this->step==2 && count($this->camposAgregados)==0){
                return $this->alert('error','Debe seleccionar los campos');
            }
            
    
            if($this->fecha_salida && $this->step==2 && isset($this->informacion['producto']) && count($this->camposAgregados)>0){
                $producto = $this->informacion['producto'];
                foreach ($this->camposAgregados as $campo) {
                    $compraActiva = CompraProducto::where('estado','1')->where('producto_id',$producto->id)->first();
                    AlmacenProductoSalida::create([                 
                        'producto_id'=>$producto->id,
                        'campo_nombre'=>$campo,
                        'fecha_reporte'=>$this->fecha_salida,
                        //'compra_producto_id'=>$compraActiva?$compraActiva->id:null,
                        /*
                        a futuro activar, pueda que una compra no este actualizada y generara conflicto
                        */
                    ]);
                }
                $this->alert('success','Registro Actualizado correctamente');
                $this->dispatch('actualizarAlmacen');
                $this->closeForm();
            }
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function elegirCampos()
    {
        $this->step = 2;
    }
    public function render()
    {
        return view('livewire.almacen-salida-productos-form-component');
    }
    public function resetCampos(){
        $this->step = 1;
        $this->informacion = [];
        $this->nombre_comercial = null;
        $this->productos = null;
        $this->camposAgregados = [];
    }
    public function closeForm(){
        
        $this->mostrarFormulario = false;
        $this->resetCampos();
    }
}
