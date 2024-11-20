<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\CompraProducto;
use App\Models\KardexProducto;
use App\Models\Producto;
use App\Services\AlmacenServicio;
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

    public $productoSeleccionado;
    public $kardexProducto;
    public $almacenes;
    public $stockDisponibleSeleccionado = 0;
    public $cantidades = [];
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
    public function nuevoRegistro($mes, $anio)
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
        if (strlen($this->nombre_comercial) > 0) { // Solo buscar si tiene más de 2 caracteres
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
        $this->productoSeleccionado = Producto::find($productoId);

        if (!$this->productoSeleccionado) {
            return;
        }

        $this->almacenes = $this->productoSeleccionado->kardexesDisponibles($this->fecha_salida);
        if (!$this->almacenes) {
            $this->alert("error", "No hay Kardex disponible para este producto, debe ir a Kardex a registrar el producto primero.");
        }
    }
    public function seleccionarKardexProducto($kardexProductoId, $stockDisponible)
    {
        $this->kardexProducto = KardexProducto::find($kardexProductoId);
        $this->stockDisponibleSeleccionado = $stockDisponible;
    }

    public function retroceder()
    {
        if ($this->step == 2) {
            $this->productoSeleccionado = null;
        }
        if ($this->step == 3) {
            $this->kardexProducto = null;
            $this->stockDisponibleSeleccionado = 0;
        }
    }
    public function store()
    {
        try {
            if (!$this->fecha_salida) {
                return $this->alert('error', 'No ha seleccionado la fecha.');
            }
            if (!$this->productoSeleccionado) {
                return $this->alert('error', 'No ha seleccionado el producto.');
            }
            if (!$this->kardexProducto) {
                return $this->alert('error', 'No ha seleccionado el almacen.');
            }
            if ($this->step == 3 && count($this->camposAgregados) == 0) {
                return $this->alert('error', 'Debe seleccionar los campos.');
            }

            if (array_sum($this->cantidades) > $this->stockDisponibleSeleccionado) {
                return $this->alert('error', 'No hay suficiente stock para esa cantidad de salida.');
            }
            
            foreach ($this->camposAgregados as $campo) {
                $cantidad = round($this->cantidades[$campo],3);
                if($cantidad>0){
                    $data = [
                        //'item',
                        'producto_id' => $this->productoSeleccionado->id,
                        'campo_nombre'=>$campo,
                        'cantidad'=>$this->cantidades[$campo],
                        'fecha_reporte'=>$this->fecha_salida,
                        //'compra_producto_id',
                        'costo_por_kg'=>null,
                        'total_costo'=>null
                    ];
    
                    AlmacenServicio::registrarSalida($data,$this->kardexProducto);
                }
                
               
            }
            $this->alert('success', 'Registro Actualizado correctamente');
            $this->dispatch('actualizarAlmacen');
            $this->closeForm();

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $this->step = 1;
        if ($this->productoSeleccionado && !$this->kardexProducto) {
            $this->step = 2;
        } else if ($this->kardexProducto) {
            $this->step = 3;
        }
        return view('livewire.almacen-salida-productos-form-component');
    }
    public function resetCampos()
    {
        /*$this->step = 1;
        $this->informacion = [];
        $this->nombre_comercial = null;
        $this->productos = null;
        $this->camposAgregados = [];*/
        $this->productoSeleccionado = null;
        $this->kardexProducto = null;
    }
    public function closeForm()
    {

        $this->mostrarFormulario = false;
        $this->resetCampos();
    }
}
