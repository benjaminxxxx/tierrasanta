<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Services\AlmacenServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $registros;
    public $registroCantidad;
    public $mostrarGenerarItem = false;
    public $cantidadNueva;
    public $inicioItem;
    public $registroIdEliminar;
    public $cantidad = [];
    protected $listeners = ['actualizarAlmacen' => '$refresh', 'ActualizarProductos' => '$refresh', 'eliminacionConfirmar'];
    public function mount($mes = null, $anio = null)
    {
        $this->mes = $mes ? $mes : Carbon::now()->format('m');
        $this->anio = $anio ? $anio : Carbon::now()->format('Y');
        
    }
    public function updatedCantidad($cantidad, $id)
    {
        $registro = AlmacenProductoSalida::find($id);
        if ($registro) {
            $fechaDesde = $registro->fecha_reporte;
            $registro->cantidad = $cantidad;
            $registro->save();            
            $compraProductoId = $registro->compra_producto_id;
            $compra = CompraProducto::find($compraProductoId);
            //dd($compra);
            if ($compra) {
                AlmacenServicio::eliminarRegistrosPosteriores($compra,$fechaDesde);
            }
            
            $this->alert("success", "Cantidad modificada correctamente");
        }
    }
    public function quitarCompraVinculada($registroId)
    {

        $registro = AlmacenProductoSalida::find($registroId);

        if ($registro) {
            
            $fechaDesde = $registro->fecha_reporte;
            $compraProductoId = $registro->compra_producto_id;
            $compra = CompraProducto::find($compraProductoId);
            if ($compra) {
                AlmacenServicio::eliminarRegistrosPosteriores($compra,$fechaDesde);
            }
            
            $this->alert("success", "Compra vinculada removida");
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
        try {
            AlmacenServicio::eliminarRegistroSalida($this->registroIdEliminar);
            $this->registroIdEliminar = null;
            $this->alert('success', "Registro eliminado");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function generarItemCodigoForm()
    {

        if ($this->registros) {
            $primerRegistro = $this->registros->first();
            if (!$primerRegistro) {
                return $this->alert("error", "Para generar la numeración, debe agregar registros");
            }

            $this->mostrarGenerarItem = true;

            $maximoItemAnterior = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes-1)
            ->whereYear('fecha_reporte', $this->anio)->max('item');
            
            if ($maximoItemAnterior) {
                $this->inicioItem = $maximoItemAnterior+1;
            } else {
                $this->inicioItem = 1;
            }
        } else {
            return $this->alert("error", "Para generar la numeración, debe agregar registros");
        }

    }
    public function generarItemCodigo()
    {
        $this->procesarRegistros();
        $this->cerrarMostrarGenerarItem();
    }
    public function procesarRegistros()
    {
        $this->obtenerRegistros();
        if ($this->registros && $this->inicioItem) {
            $correlativo = $this->inicioItem;
            $replicated = false; // Variable para verificar si se replicó un registro

            foreach ($this->registros as $registro) {
                if ($registro->cantidad) {
                    if (!$registro->compra_producto_id) {
                        $compraActiva = CompraProducto::where('producto_id', $registro->producto_id)
                            ->whereNull('fecha_termino')
                            ->orderBy('fecha_compra')
                            ->first();

                        if ($compraActiva) {
                            $cantidadUsada = AlmacenProductoSalida::where('compra_producto_id', $compraActiva->id)->sum('cantidad');
                            $stockDisponible = (float) $compraActiva->stock - (float) $cantidadUsada;

                            if ($stockDisponible >= $registro->cantidad) {
                                $registro->compra_producto_id = $compraActiva->id;
                                $registro->costo_por_kg = $compraActiva->costo_por_kg;
                                $registro->total_costo = (float) $compraActiva->costo_por_kg * (float) $registro->cantidad;
                                $registro->item = $correlativo;
                                $correlativo++;
                                $registro->save();

                                if ($stockDisponible - $registro->cantidad == 0.00) {
                                    $compraActiva->fecha_termino = $registro->fecha_reporte;
                                    $compraActiva->save();
                                }
                            } else {
                                if ($stockDisponible > 0) {
                                    $nuevaCantidad = $registro->cantidad - $stockDisponible;
                                    $registro->cantidad = $stockDisponible;
                                    $registro->compra_producto_id = $compraActiva->id;
                                    $registro->costo_por_kg = $compraActiva->costo_por_kg;
                                    $registro->total_costo = (float) $compraActiva->costo_por_kg * (float) $stockDisponible;
                                    $registro->save();

                                    $compraActiva->fecha_termino = $registro->fecha_reporte;
                                    $compraActiva->save();

                                    // Replicación y adición del nuevo registro
                                    $nuevoRegistro = $registro->replicate();
                                    $nuevoRegistro->cantidad = $nuevaCantidad;
                                    $nuevoRegistro->item = null;
                                    $nuevoRegistro->compra_producto_id = null;
                                    $nuevoRegistro->costo_por_kg = null;
                                    $nuevoRegistro->total_costo = null;
                                    $nuevoRegistro->save();

                                    // Agrega el nuevo registro a la lista de registros
                                    $this->registros->push($nuevoRegistro);
                                    $this->registros->sortBy('fecha_reporte')->sortBy('campo_nombre');

                                    // Marca que ocurrió una replicación y termina el bucle
                                    $replicated = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $registro->item = $correlativo;
                        $correlativo++;
                        $registro->save();
                    }
                } else {
                    $registro->item = null;
                    $registro->save();
                }
            }

            // Si ocurrió una replicación, vuelve a ejecutar el bucle desde el inicio
            if ($replicated) {
                $this->procesarRegistros();
            }
        }
    }

    public function cerrarMostrarGenerarItem()
    {
        $this->mostrarGenerarItem = false;
        $this->inicioItem = null;
    }
    public function obtenerRegistros(){
        if ($this->mes && $this->anio) {
            $this->registros = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes)
                ->whereYear('fecha_reporte', $this->anio)
                ->orderBy('fecha_reporte')
                ->orderBy('campo_nombre')
                ->orderBy('created_at')
                ->get();
            $this->cantidad = $this->registros->pluck('cantidad', 'id')->toArray();

        }
    }
   
    public function render()
    {
        $this->obtenerRegistros();
        return view('livewire.almacen-salida-detalle-component');
    }
}
