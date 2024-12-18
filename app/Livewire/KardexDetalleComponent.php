<?php

namespace App\Livewire;

use App\Exports\KardexAlmacenExport;
use App\Exports\KardexProductoExport;
use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\Empresa;
use App\Models\Kardex;
use App\Models\Producto;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\KardexProducto;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class KardexDetalleComponent extends Component
{
    use LivewireAlert;
    public $kardexId;
    public $kardex;
    public $kardexDetalleProductos = [];
    public $kardexLista = [];
    public $productoKardexSeleccionado;
    public $file;
    public $kardexProducto;
    public $metodoValuacion = "promedio";
    public $kardexCalculado = false;
    public $empresa;
    public $esCombustible = false;
    public $totalCompras = 0;
    public $totalSalidas = 0;
    public $registroIdEliminar;
    protected $listeners = ['kardexProductoRegistrado' => 'listarKardex', 'importacionRealizada' => 'listarKardex', 'eliminacionConfirmar'];
    public function mount()
    {
        $this->kardex = Kardex::find($this->kardexId);
        $this->empresa = Empresa::first();
    }
    /*public function recalcularCostos()
    {
        if (!$this->kardex) {
            return;
        }
        if (!$this->empresa) {
            return $this->alert('error', 'No hay datos de empresa registrada.');
        }

        $periodo = Carbon::parse($this->kardex->fecha_inicial)->format('Y');

        $data = [
            'kardexId' => $this->kardexId,
            'productoId' => $this->productoKardexSeleccionado,
            'esCombustible'=>$this->esCombustible,
            'kardexLista' => $this->kardexLista,
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $this->kardexProducto->codigo_existencia,
                'tipo' => $this->kardexProducto->producto->tabla5->codigo . ' - ' . $this->kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $this->kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $this->kardexProducto->producto->tabla6->codigo . ' - ' . $this->kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ]
        ];

        $filePath = 'kadex/' . date('Y-m') . '/' . $this->kardexProducto->codigo_existencia . '_' . Str::slug($this->kardexProducto->producto->nombre_completo) . '.xlsx';
        $file = Excel::store(new KardexProductoExport($data), $filePath, 'public');
        $this->kardexProducto->file = $filePath;
        $this->kardexProducto->save();
        $this->dispatch('procesarFile', $filePath);
    }
    public function descargarKardex()
    {
        if (!$this->kardex) {
            return;
        }
        if (!$this->empresa) {
            return $this->alert('error', 'No hay datos de empresa registrada.');
        }

        $periodo = Carbon::parse($this->kardex->fecha_inicial)->format('Y');

        $data = [
            'kardexId' => $this->kardexId,
            'productoId' => $this->productoKardexSeleccionado,
            'kardexLista' => $this->kardexLista,
            'esCombustible'=>$this->esCombustible,
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $this->kardexProducto->codigo_existencia,
                'tipo' => $this->kardexProducto->producto->tabla5->codigo . ' - ' . $this->kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $this->kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $this->kardexProducto->producto->tabla6->codigo . ' - ' . $this->kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ]
        ];

        return Excel::download(new KardexProductoExport($data), 'kardex_almacen.xlsx');

    }*/
    protected function obtenerDatosKardex()
    {
        if (!$this->kardex) {
            return null;
        }
        if (!$this->empresa) {
            $this->alert('error', 'No hay datos de empresa registrada.');
            return null;
        }

        $tieneTipo = $this->kardexProducto->producto->tabla5;
        if(!$tieneTipo){
            return $this->alert('error', 'El producto no tiene un tipo, editar el producto.');
        }

        $periodo = Carbon::parse($this->kardex->fecha_inicial)->format('Y');

        return [
            'kardexId' => $this->kardexId,
            'productoId' => $this->productoKardexSeleccionado,
            'esCombustible' => $this->esCombustible,
            'kardexLista' => $this->kardexLista,
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $this->kardexProducto->codigo_existencia,
                'tipo' => $this->kardexProducto->producto->tabla5->codigo . ' - ' . $this->kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $this->kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $this->kardexProducto->producto->tabla6->codigo . ' - ' . $this->kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ],
        ];
    }

    public function recalcularCostos()
    {
        $this->listarKardex();
        $data = $this->obtenerDatosKardex();

        if (is_null($data)) {
            return;
        }

        $filePath = 'kadex/' . date('Y-m') . '/' .
            $this->kardexProducto->codigo_existencia . '_' .
            Str::slug($this->kardexProducto->producto->nombre_completo) .
            '.xlsx';

        $file = Excel::store(new KardexProductoExport($data), $filePath, 'public');
        $this->kardexProducto->file = $filePath;
        $this->kardexProducto->save();

        $this->dispatch('procesarFile', $filePath);
    }

    public function descargarKardex()
    {
        $data = $this->obtenerDatosKardex();

        if (is_null($data)) {
            return;
        }

        return Excel::download(new KardexProductoExport($data), 'kardex_almacen.xlsx');
    }


    public function listarKardex()
    {
        if ($this->kardex) {
            $this->kardexProducto = KardexProducto::where('producto_id', $this->productoKardexSeleccionado)
                ->where('kardex_id', $this->kardexId)->first();
        }

        if (!$this->kardexProducto) {
            return;
        }

        $this->totalCompras = 0;
        $this->totalSalidas = 0;

        $this->esCombustible = Producto::esCombustible($this->productoKardexSeleccionado);

        $this->kardexLista = [];

        $this->kardexLista[] = [
            'tipo' => 'a',
            'fecha' => $this->kardex->fecha_inicial,
            'tabla10' => '',
            'serie' => '',
            'numero' => '',
            'tipo_operacion' => 16, //SALDO INICIAL
            'entrada_cantidad' => $this->kardexProducto->stock_inicial,
            'entrada_costo_unitario' => $this->kardexProducto->costo_unitario,
            'entrada_costo_total' => $this->kardexProducto->costo_total,
            'salida_cantidad' => '',
            'salida_lote' => '',
            'salida_maquinaria' => '',
            'salida_costo_unitario' => '',
            'salida_costo_total' => '',
            'saldofinal_cantidad' => '',
            'saldofinal_costo_unitario' => '',
            'saldofinal_costo_total' => '',
        ];

        $compras = CompraProducto::where('producto_id', $this->productoKardexSeleccionado)
            ->where('tipo_kardex', $this->kardex->tipo_kardex)
            ->whereBetween('fecha_compra', [$this->kardex->fecha_inicial, $this->kardex->fecha_final])
            ->get();

        $salidas = AlmacenProductoSalida::where('producto_id', $this->productoKardexSeleccionado)
            ->where('kardex_producto_id', $this->kardexProducto->id)
            ->whereBetween('fecha_reporte', [$this->kardex->fecha_inicial, $this->kardex->fecha_final])
            ->get();

        if ($compras) {
            foreach ($compras as $compra) {
                $this->kardexLista[] = [
                    'tipo' => 'compra',
                    'compra_id' => $compra->id,
                    'fecha' => $compra->fecha_compra,
                    'tabla10' => $compra->tipo_compra_codigo,
                    'serie' => $compra->serie,
                    'numero' => $compra->numero,
                    'tipo_operacion' => $compra->tabla12_tipo_operacion,
                    'entrada_cantidad' => $compra->stock,
                    'entrada_costo_unitario' => $compra->costo_por_unidad,
                    'entrada_costo_total' => $compra->total,
                    'salida_cantidad' => '',
                    'salida_lote' => '',
                    'salida_maquinaria' => '',
                    'salida_costo_unitario' => '',
                    'salida_costo_total' => '',
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];
                $this->totalCompras++;
            }
        }
        if ($salidas) {
            foreach ($salidas as $salida) {
                $this->kardexLista[] = [
                    'tipo' => 'salida',
                    'salida_id' => $salida->id,
                    'fecha' => $salida->fecha_reporte,
                    'tabla10' => '',
                    'serie' => '',
                    'numero' => '',
                    'tipo_operacion' => 10, //SALIDA A PRODUCCION
                    'entrada_cantidad' => '',
                    'entrada_costo_unitario' => '',
                    'entrada_costo_total' => '',
                    'salida_cantidad' => $salida->cantidad,
                    'salida_lote' => $salida->campo_nombre,
                    'salida_maquinaria' => $salida->maquina_nombre,
                    'salida_costo_unitario' => $salida->costo_por_kg,
                    'salida_costo_total' => $salida->total_costo,
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];
                $this->totalSalidas++;
            }
        }

        $fecha = array_column($this->kardexLista, 'fecha');
        $tipo = array_column($this->kardexLista, 'tipo');

        array_multisort($fecha, SORT_ASC, $tipo, SORT_ASC, $this->kardexLista);
    }
    public function updatedProductoKardexSeleccionado()
    {
        $this->listarKardex();
    }
    public function eliminarComprasySalidas($kardexProductoId)
    {
        $this->registroIdEliminar = $kardexProductoId;

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
        if (!$this->registroIdEliminar) {
            return;
        }

        
        $salidas = CompraSalidaStock::where('kardex_producto_id', $this->registroIdEliminar)->get();
        foreach ($salidas as $salida) {
            $compra = CompraProducto::find($salida->compra_producto_id);
            if ($compra) {
                $compra->delete();
            }
        }
        AlmacenProductoSalida::where('kardex_producto_id', $this->registroIdEliminar)->delete();
        AlmacenProductoSalida::where('cantidad_kardex_producto_id', $this->registroIdEliminar)->delete();
        $this->listarKardex();
        $this->alert("success", "Registros eliminados correctamente.");
        $this->registroIdEliminar = null;
    }
    public function render()
    {
        if ($this->kardex) {
            $this->kardexDetalleProductos = KardexProducto::where('kardex_id', $this->kardexId)->get();
        }

        return view('livewire.kardex-detalle-component');
    }
}
