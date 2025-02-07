<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\KardexProducto;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaProductosFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $productos;
    public $nombre_comercial;
    public $camposAgregados = [];
    public $maquinariasAgregadas = [];
    public $campos;
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
    public $maquinariasNombres = [];
    public $destino;
    protected $listeners = ['nuevoRegistro'];
    public function mount($destino = 'productos')
    {
        $this->destino = $destino;

        $this->obtenerFechaSalida();
        if ($this->destino == 'productos') {
            $this->campos = Campo::orderBy('orden')->get();
        }
        if ($this->destino == 'combustible') {
            $this->maquinarias = Maquinaria::all();
            $this->maquinariasNombres = $this->maquinarias->pluck('nombre', 'id')->toArray();
        }
    }
    public function seleccionarKardexProducto($kardexProductoId, $stockDisponible)
    {
        $this->kardexProducto = KardexProducto::find($kardexProductoId);
        $this->stockDisponibleSeleccionado = $stockDisponible;
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
        $this->resetForm();
        $this->obtenerFechaSalida();
        $this->mostrarFormulario = true;
    }
    public function updatedNombreComercial()
    {
        try {
            if (strlen($this->nombre_comercial) > 0) { // Solo buscar si tiene más de 2 caracteres
                if ($this->destino == 'productos') {
                    $this->productos = Producto::where('nombre_comercial', 'like', '%' . $this->nombre_comercial . '%')
                        ->orWhere('ingrediente_activo', 'like', '%' . $this->nombre_comercial . '%')
                        ->take(5) // Limitar los resultados a 5 para no saturar la lista flotante
                        ->get();
                }
                if ($this->destino == 'combustible') {
                    $this->productos = Producto::buscarCombustible($this->nombre_comercial);
                }
            } else {
                $this->productos = [];
            }
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }
    public function seleccionarProducto($productoId)
    {
        try {
            $this->productoSeleccionado = Producto::find($productoId);
            if (!$this->productoSeleccionado) {
                return;
            }

            $this->almacenes = $this->productoSeleccionado->kardexesDisponibles($this->fecha_salida);


            if (!$this->almacenes) {
                $this->alert("error", "No hay Kardex disponible para este producto, debe ir a Kardex a registrar el producto primero.");
            }
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
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
           
            if($this->destino=='combustible'){
                if ($this->step == 3 && count($this->maquinariasAgregadas) == 0) {
                    return $this->alert('error', 'Debe seleccionar los maquinarias.');
                }
            }else{
                if ($this->step == 3 && count($this->camposAgregados) == 0) {
                    return $this->alert('error', 'Debe seleccionar los campos.');
                }
            }

            if (array_sum($this->cantidades) > $this->stockDisponibleSeleccionado) {
                return $this->alert('error', 'No hay suficiente stock para esa cantidad de salida.');
            }

            $data = [];

            if($this->destino=='combustible'){
                foreach ($this->maquinariasAgregadas as $maquinariaId) {
                    $cantidad = round($this->cantidades[$maquinariaId],3);
                    if($cantidad>0){
                        $data[] = [
                            'producto_id' => $this->productoSeleccionado->id,
                            'campo_nombre'=>'',
                            'cantidad'=>$this->cantidades[$maquinariaId],
                            'fecha_reporte'=>$this->fecha_salida,
                            'costo_por_kg'=>null,
                            'maquinaria_id'=>$maquinariaId,
                            'total_costo'=>null
                        ];
                    }
                }
                
            }else{
                foreach ($this->camposAgregados as $indice => $campo) {
                    $cantidad = round($this->cantidades[$campo], 3);
                    if ($cantidad > 0) {
                        $data[] = [
                            'producto_id' => $this->productoSeleccionado->id,
                            'campo_nombre' => $campo,
                            'cantidad' => $this->cantidades[$campo],
                            'fecha_reporte' => $this->fecha_salida,
                            'costo_por_kg' => null,
                            'total_costo' => null,
                            'indice' => $indice,
                            'tipo_kardex' => $this->kardexProducto->tipo_kardex
                        ];
                    }
                }
            }

            

            if (count($data) > 0) {
                AlmacenServicio::registrarSalida($data);
            } else {
                return $this->alert('error', 'Ningún registro tiene la cantidad para ser registrado');
            }

            $this->alert('success', 'Registro Actualizado correctamente');
            $this->dispatch('actualizarAlmacen');
            $this->closeForm();

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al registrar la salida.');
        }
    }
    public function resetForm()
    {
        $this->cantidades = [];
        $this->reset(['nombre_comercial', 'productoSeleccionado', 'productos', 'camposAgregados', 'kardexProducto','maquinariasAgregadas']);
    }
    public function closeForm()
    {
        $this->mostrarFormulario = false;
        $this->resetForm();
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
}
