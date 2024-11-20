<?php

namespace App\Livewire;

use App\Exports\KardexAlmacenExport;
use App\Exports\KardexProductoExport;
use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\Empresa;
use App\Models\Kardex;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\KardexProducto;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Str;

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
    protected $listeners = ['kardexProductoRegistrado' => 'listarKardex', 'importacionRealizada' => 'listarKardex'];
    public function mount()
    {
        $this->kardex = Kardex::find($this->kardexId);
        $this->empresa = Empresa::first();
    }
    public function recalcularCostos()
    {
        /*
        foreach ($this->kardexLista as $key => $fila) {

            if ($key == 0) {
                $this->kardexLista[$key]['saldofinal_cantidad'] = $this->kardexLista[$key]['entrada_cantidad'];
                $this->kardexLista[$key]['saldofinal_costo_unitario'] = $this->kardexLista[$key]['entrada_costo_unitario'];
                $this->kardexLista[$key]['saldofinal_costo_total'] = $this->kardexLista[$key]['entrada_costo_total'];
            } else {
                $filaAnterior = $this->kardexLista[$key - 1];

                // Operaciones con bc math
                $entradaCantidad = (float) $this->kardexLista[$key]['entrada_cantidad'];
                $salidaCantidad = (float) $this->kardexLista[$key]['salida_cantidad'];
                $saldoFinalCantidad = bcsub(
                    bcadd($entradaCantidad, (float) $filaAnterior['saldofinal_cantidad'], 10),
                    $salidaCantidad,
                    10
                );

                $entradaCostoTotal = (float) $this->kardexLista[$key]['entrada_costo_total'];
                $salidaCostoTotal = (float) $this->kardexLista[$key]['salida_costo_total'];
                $saldoFinalTotal = bcsub(
                    bcadd((float) $filaAnterior['saldofinal_costo_total'], $entradaCostoTotal, 10),
                    $salidaCostoTotal,
                    10
                );

                // Prevenir valores negativos acumulados menores a 0.05
                if ($saldoFinalTotal < 0.05) {
                    $saldoFinalTotal = 0;
                }

                // Calcular el costo unitario
                $costoUnitario = ($saldoFinalCantidad > 0)
                    ? bcdiv($saldoFinalTotal, $saldoFinalCantidad, 10)
                    : 0;

                $this->kardexLista[$key]['saldofinal_cantidad'] = round($saldoFinalCantidad, 2);
                $this->kardexLista[$key]['saldofinal_costo_unitario'] = round($costoUnitario, 10);
                $this->kardexLista[$key]['saldofinal_costo_total'] = round($saldoFinalTotal, 10);

                // Ajustar costos de salida
                if ($this->kardexLista[$key]['tipo'] == 'salida') {
                    $this->kardexLista[$key]['salida_costo_unitario'] = $filaAnterior['saldofinal_costo_unitario'];
                    $this->kardexLista[$key]['salida_costo_total'] = round(
                        $this->kardexLista[$key]['salida_cantidad'] * $this->kardexLista[$key]['salida_costo_unitario'],
                        10
                    );
                }
            }
        }
        $this->kardexCalculado = true;*/
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
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $this->kardexProducto->producto->codigo_existencia,
                'tipo' => $this->kardexProducto->producto->tabla5->codigo . ' - ' . $this->kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $this->kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $this->kardexProducto->producto->tabla6->codigo . ' - ' . $this->kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ]
        ];

        $filePath = 'kadex/' . date('Y-m') . '/' . $this->kardexProducto->producto->codigo_existencia . '_' . Str::slug($this->kardexProducto->producto->nombre_completo) . '.xlsx';
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
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $this->kardexProducto->producto->codigo_existencia,
                'tipo' => $this->kardexProducto->producto->tabla5->codigo . ' - ' . $this->kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $this->kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $this->kardexProducto->producto->tabla6->codigo . ' - ' . $this->kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ]
        ];

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
            ->where('kardex_producto_id',$this->kardexProducto->id)
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
                    'entrada_costo_unitario' => $compra->costo_por_kg,
                    'entrada_costo_total' => $compra->total,
                    'salida_cantidad' => '',
                    'salida_lote' => '',
                    'salida_costo_unitario' => '',
                    'salida_costo_total' => '',
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];

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
                    'salida_costo_unitario' => $salida->costo_por_kg,
                    'salida_costo_total' => $salida->total_costo,
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];

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
    public function render()
    {
        if ($this->kardex) {
            $this->kardexDetalleProductos = KardexProducto::where('kardex_id', $this->kardexId)->get();
        }

        return view('livewire.kardex-detalle-component');
    }
}
