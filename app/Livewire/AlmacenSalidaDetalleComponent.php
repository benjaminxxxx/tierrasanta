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
    public $mostrarGenerarItem = false;
    public $inicioItem;
    public $cantidad = [];
    public $tipo;
    protected $listeners = ['actualizarAlmacen' => '$refresh', 'ActualizarProductos' => '$refresh', 'eliminacionConfirmar'];
    public function mount($mes = null, $anio = null)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->cargarSalidaInsumos();
    }
    /*
    public function confirmarEliminacionSalida($id)
    {
        $this->confirm('¿Está seguro que desea eliminar el registro?', [
            'onConfirmed' => 'eliminacionConfirmar',
            'data' => ['id' => $id],
        ]);
    }
    public function eliminacionConfirmar($data)
    {
        try {
            AlmacenServicio::eliminarRegistroSalida($data['id']);
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
    }*/
    public function procesarRegistros()
    {

        if ($this->registros && $this->inicioItem) {
            $correlativo = $this->inicioItem;

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
        }
    }

    public function cerrarMostrarGenerarItem()
    {
        $this->mostrarGenerarItem = false;
        $this->inicioItem = null;
    }
    public function cargarSalidaInsumos()
    {
        $this->registros = AlmacenServicio::obtenerRegistrosPorFecha($this->mes, $this->anio, $this->tipo)
            ->map(function ($salida) {
                return array_merge($salida->toArray(), [
                    'campo_nombre' => $this->tipo == 'combustible' ? $salida->maquina_nombre : $salida->campo_nombre,
                    'nombre_producto' => $salida->producto?->nombre_comercial,
                    'unidad_medida' => $salida->producto?->unidad_medida,
                    'categoria' => $salida->producto?->categoria?->descripcion,
                ]);
            })
            ->toArray();
    }
    public function render()
    {/*
      if ($this->mes && $this->anio) {
          $this->registros = AlmacenServicio::obtenerRegistrosPorFecha($this->mes, $this->anio, $this->tipo);
          dd($this->registros);
          $this->cantidad = $this->registros->pluck('cantidad', 'id')->toArray();
      }*/
        return view('livewire.almacen-salida-detalle-component');
    }
}
