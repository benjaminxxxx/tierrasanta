<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaProductosComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $registros;
    public $registroCantidad;
    public $mostrarCambiarCantidad = false;
    public $mostrarGenerarItem = false;
    public $cantidadNueva;
    public $inicioItem;
    public $registroIdEliminar;
    protected $listeners = ['actualizarAlmacen'=>'$refresh','ActualizarProductos' => '$refresh','eliminacionConfirmar'];
    public function mount($mes=null,$anio=null)
    {
        $this->mes = $mes?$mes:Carbon::now()->format('m');
        $this->anio = $anio?$anio:Carbon::now()->format('Y');
    }

    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }
    public function elegirCompra($compraId,$registroId){
        
        $registro = AlmacenProductoSalida::find($registroId);
        if($registro){
            $registro->compra_producto_id = $compraId;
            $registro->save();
        }
    }
    public function cambiarCantidad($registroId){
        
        $this->registroCantidad = AlmacenProductoSalida::find($registroId);
        if($this->registroCantidad){
            $this->mostrarCambiarCantidad = true;
            $this->cantidadNueva = $this->registroCantidad->cantidad;
        }else{
            return $this->alert('error','El registro no existe');
        }        
    }
    public function guardarCantidadNueva(){
        if($this->registroCantidad){
            $this->registroCantidad->cantidad =  $this->cantidadNueva;
            $this->registroCantidad->save();
            $this->cerrarMostrarCambiarCantidad();
            $this->alert('success','La cantidad se ha guardado con exito.');
        }else{
            return $this->alert('error','El registro no existe');
        }
    }
    public function confirmarEliminacion($id)
    {
        $this->registroIdEliminar = $id;

        $this->alert('question', '¿Está seguro que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function eliminacionConfirmar()
    {
        if ($this->registroIdEliminar) {
            $registro = AlmacenProductoSalida::find($this->registroIdEliminar);
            if ($registro) {
                $registro->delete();
                $this->registroIdEliminar = null;
                $this->alert('success', 'Registro Eliminado');
            }
        }
    }
    public function generarItemCodigoForm(){
        $this->mostrarGenerarItem = true;
        if($this->registros){
            $primerRegistro = $this->registros->first();
            if($primerRegistro->item){
                $this->inicioItem = $primerRegistro->item;
            }else{
                $this->inicioItem = 0;
            }
        }
        
    }
    public function generarItemCodigo(){
   
        if($this->registros && $this->inicioItem){
            $correlativo = $this->inicioItem;
            foreach ($this->registros as $registro) {
                $registro->item = $correlativo;
                $registro->save();
                $correlativo++;
            }
            
        }
        $this->cerrarMostrarGenerarItem();
    }
    public function cerrarMostrarCambiarCantidad(){
        $this->mostrarCambiarCantidad = false;
        $this->registroCantidad = null;
        $this->cantidadNueva = null;
    }
    public function cerrarMostrarGenerarItem(){
        $this->mostrarGenerarItem = false;
        $this->inicioItem = null;
    }
    public function render()
    {

        if ($this->mes && $this->anio) {
            $this->registros = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes)
                                ->whereYear('fecha_reporte', $this->anio)
                                ->orderBy('fecha_reporte')->orderBy('created_at')
                                ->get();
        }
        return view('livewire.almacen-salida-productos-component');
    }
}
