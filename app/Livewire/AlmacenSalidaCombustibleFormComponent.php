<?php

namespace App\Livewire;

use App\Models\KardexProducto;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaCombustibleFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $productos;
    public $nombre_comercial;
    public $informacion = [];
    public $maquinariasAgregadas = [];
    public $maquinarias;
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
        $this->maquinarias = Maquinaria::all();
    }
    public function obtenerFechaSalida()
    {
        $mesActual = Carbon::now()->month;
        $anioActual = Carbon::now()->year;

        if ($this->mes && $this->anio) {
            
            if ($this->mes == $mesActual && $this->anio == $anioActual) {
                $this->fecha_salida = Carbon::now()->format('Y-m-d');
            } else {
                $this->fecha_salida = Carbon::create($this->anio, $this->mes, 1)->format('Y-m-d');
            }
        } else {
            $this->fecha_salida = Carbon::now()->format('Y-m-d');
        }
    }
    public function toggleMaquinaria($maquinariaId)
    {
        if (in_array($maquinariaId, $this->maquinariasAgregadas)) {
            // Eliminar el campo si ya está seleccionado
            $this->maquinariasAgregadas = array_diff($this->maquinariasAgregadas, [$maquinariaId]);
        } else {
            // Agregar el campo si no está seleccionado
            $this->maquinariasAgregadas[] = $maquinariaId;
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
     
        try {
            if (strlen($this->nombre_comercial) > 0) { // Solo buscar si tiene más de 2 caracteres
                $this->productos = Producto::buscarCombustible($this->nombre_comercial);
            } else {
                $this->productos = [];
            }
           
        } catch (Exception $e) {
            $this->alert("error", $e->getMessage());
        }
    }
    public function seleccionarProducto($productoId)
    {
        $this->productoSeleccionado = Producto::find($productoId);
       
        if (!$this->productoSeleccionado) {
            return;
        }

        $this->almacenes = $this->productoSeleccionado->kardexesDisponibles($this->fecha_salida); //retorna items [] en caso no haya nada, error manejado
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
            if ($this->step == 3 && count($this->maquinariasAgregadas) == 0) {
                return $this->alert('error', 'Debe seleccionar los maquinarias.');
            }

            if (array_sum($this->cantidades) > $this->stockDisponibleSeleccionado) {
                return $this->alert('error', 'No hay suficiente stock para esa cantidad de salida.');
            }
            
            foreach ($this->maquinariasAgregadas as $maquinariaId) {
                $cantidad = round($this->cantidades[$maquinariaId],3);
                if($cantidad>0){
                    $data = [
                        //'item',
                        'producto_id' => $this->productoSeleccionado->id,
                        'campo_nombre'=>'',
                        'cantidad'=>$this->cantidades[$maquinariaId],
                        'fecha_reporte'=>$this->fecha_salida,
                        //'compra_producto_id',
                        'costo_por_kg'=>null,
                        'maquinaria_id'=>$maquinariaId,
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
    public function resetCampos()
    {
        $this->productoSeleccionado = null;
        $this->kardexProducto = null;
    }
    public function closeForm()
    {

        $this->mostrarFormulario = false;
        $this->resetCampos();
    }
    public function render()
    {
        $this->step = 1;
        if ($this->productoSeleccionado && !$this->kardexProducto) {
            $this->step = 2;
        } else if ($this->kardexProducto) {
            $this->step = 3;
        }

        return view('livewire.almacen-salida-combustible-form-component');
    }
}
