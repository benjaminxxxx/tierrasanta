<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
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
    public $tipo;
    protected $listeners = ['actualizarAlmacen' => '$refresh', 'ActualizarProductos' => '$refresh', 'eliminacionConfirmar'];
    public function mount($mes = null, $anio = null)
    {
        $this->mes = $mes ? $mes : Carbon::now()->format('m');
        $this->anio = $anio ? $anio : Carbon::now()->format('Y');

    }
    public function updatedCantidad($cantidad, $id)
    {
        try {
            $registro = AlmacenProductoSalida::find($id);
            if ($registro) {
                $registro->cantidad = $cantidad;
                $registro->costo_por_kg = null;
                $registro->total_costo = null;
                $registro->save();

                $registro->compraStock()->delete();
            }

            $this->alert("success", "Cantidad modificada correctamente");
        } catch (\Throwable $th) {

            $this->alert('error', $th->getMessage(), [

                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
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

            $maximoItemAnterior = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes - 1)
                ->whereYear('fecha_reporte', $this->anio)->max('item');

            if ($maximoItemAnterior) {
                $this->inicioItem = $maximoItemAnterior + 1;
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
                    $registro->item = $correlativo;
                    $correlativo++;
                    $registro->save();

                   
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
    public function obtenerRegistros()
    {
        if ($this->mes && $this->anio) {
            $this->registros = AlmacenServicio::obtenerRegistrosPorFecha($this->mes, $this->anio, $this->tipo);
            $this->cantidad = $this->registros->pluck('cantidad', 'id')->toArray();
        }
    }

    public function render()
    {
        $this->obtenerRegistros();
        return view('livewire.almacen-salida-detalle-component');
    }
}
