<?php

namespace App\Livewire;

use App\Models\Campo;
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
    public $almacenes;
    public $cantidades = [];
    public $maquinariasNombres = [];
    public $destino;
    public $modoFdm = false;
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
    public function seleccionarProductoParaSalida($productoId)
    {
        try {
            
            $this->productoSeleccionado = Producto::find($productoId);
            if (!$this->productoSeleccionado) {
                throw new Exception("Producto no encontrado.");
            }
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }

    public function retroceder()
    {
        if ($this->step == 2) {
            $this->productoSeleccionado = null;
            $this->nombre_comercial = '';
            $this->productos = [];
        }
    }
    public function store()
    {
        try {
            if (!$this->fecha_salida) {
                throw new Exception('No ha seleccionado la fecha.');
            }
            if (!$this->productoSeleccionado) {
                throw new Exception('No ha seleccionado el producto.');
            }
           
            if($this->destino=='combustible'){
                if ($this->step == 2 && count($this->maquinariasAgregadas) == 0) {
                    return $this->alert('error', 'Debe seleccionar las maquinarias o centro de costos.');
                }
            }else{
                if ($this->step == 2 && count($this->camposAgregados) == 0) {
                    return $this->alert('error', 'Debe seleccionar los campos.');
                }
            }
            $data = [];

            if($this->destino=='combustible'){
                if(is_array($this->maquinariasAgregadas) && count($this->maquinariasAgregadas)>0){
                    foreach ($this->maquinariasAgregadas as $indice => $maquinariaId) {
                        $cantidad = round($this->cantidades[$maquinariaId],3);
                        if($cantidad>0){
                            $data[] = [
                                'producto_id' => $this->productoSeleccionado->id,
                                'campo_nombre'=>$this->modoFdm?'fdm':'',
                                'cantidad'=>$this->cantidades[$maquinariaId],
                                'fecha_reporte'=>$this->fecha_salida,
                                'costo_por_kg'=>null,
                                'maquinaria_id'=>$maquinariaId,
                                'total_costo'=>null,
                                'indice' => $indice,
                                'tipo_kardex' => null,
                            ];
                        }
                    }
                }
               
                
            }else{
                
                foreach ($this->camposAgregados as $indice => $campo) {
                    $cantidad = round($this->cantidades[$campo]??0, 3);
                    
                    if ($cantidad > 0) {
                        $data[] = [
                            'producto_id' => $this->productoSeleccionado->id,
                            'campo_nombre' => $campo,
                            'cantidad' => $this->cantidades[$campo],
                            'fecha_reporte' => $this->fecha_salida,
                            'costo_por_kg' => null,
                            'total_costo' => null,
                            'indice' => $indice,
                            'tipo_kardex' => null
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
            $this->alert('error', $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->cantidades = [];
        $this->reset(['nombre_comercial', 'productoSeleccionado', 'productos', 'camposAgregados','maquinariasAgregadas']);
    }
    public function closeForm()
    {
        $this->mostrarFormulario = false;
        $this->resetForm();
    }
    public function render()
    {
        $this->step = 1;
        if ($this->productoSeleccionado) {
            $this->step = 2;
        }
        
        return view('livewire.almacen-salida-productos-form-component');
    }
}
