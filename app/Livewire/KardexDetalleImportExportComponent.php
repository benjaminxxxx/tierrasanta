<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\Kardex;
use App\Models\KardexProducto;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\ProductoServicio;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KardexDetalleImportExportComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    public $file;
    public $kardexId;
    public $productoId;
    public $producto;
    public $kardex;
    public $kardexProducto;
    public function mount(){
        $this->kardex = Kardex::find($this->kardexId);
        $this->producto = Producto::find($this->productoId);
        $this->kardexProducto = KardexProducto::where('kardex_id',$this->kardexId)
        ->where('producto_id',$this->productoId)
        ->first();
    }
    public function updatedFile()
    {
        if ($this->file) {

            try {
                
                $this->validate([
                    'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
                ]);
                
                $spreadsheet = IOFactory::load($this->file->getRealPath());

                $this->procesarKardexSheet($spreadsheet);

                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('KardexImportado');
            } catch (Exception $e) {
                //$this->alert('error', $e->getMessage());
                $this->alert('error', $e->getMessage(), [
                 
                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
        }
    }
    protected function procesarKardexSheet($spreadsheet)
    {
        if(!$this->kardex){
            throw new Exception("El kardex no es válido.");
        }
        if (!$this->producto) {
            throw new Exception("El producto no existe.");
        }
        if(!$this->kardexProducto){
            throw new Exception("El kardex del producto no es válido.");
        }
        if (trim($this->producto->codigo_existencia)=='') {
            throw new Exception("El producto no tiene un código de existencia válido, debe actualizar la información.");
        }
        
        $sheet = $spreadsheet->getSheetByName($this->producto->codigo_existencia);

        if (!$sheet) {
            throw new Exception("El Excel no tiene una hoja llamada: " . $this->producto->codigo_existencia);
        }

        $rows = $sheet->toArray();

        $indiceInicio = 16;
        $indiceColumnaFecha = 0;
        $indiceColumnaTabla12 = 4;

        if(!isset($rows[$indiceInicio])){
            throw new Exception("El archivo no tiene el formato correcto, la información debe iniciar en la fila: " . ($indiceInicio + 1));
        }
        if(!isset($rows[$indiceInicio][$indiceColumnaFecha])){
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($indiceColumnaFecha + 1) . " para fechas");
        }
        if(!isset($rows[$indiceInicio][$indiceColumnaTabla12])){
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($indiceColumnaTabla12 + 1));
        }

        if((int)$rows[$indiceInicio][$indiceColumnaTabla12]!=16){
            throw new Exception("El archivo no tiene el formato correcto, la celda E17 debe tener el codigo 16: SALDO INICIAL");
        }

        //formato 1/1/2024 o 01/01/2024
        $fecha = Carbon::parse($rows[$indiceInicio][$indiceColumnaFecha]);
        if(!$fecha->isValid()){
            throw new Exception("La fecha " . $rows[$indiceInicio][$indiceColumnaFecha] . " no es válida");
        }

        $fechaKardex = Carbon::parse($this->kardex->fecha_inicial)->format('Y-m-d');
        if($fechaKardex !=$fecha->format('Y-m-d')){
            throw new Exception("La fecha " . $fechaKardex . " no coindice con la fecha inicial del Kardex: " . $fecha->format('Y-m-d'));
        }

        $fechaAnterior = Carbon::parse($this->kardex->fecha_inicial);

        AlmacenServicio::resetearStocks($this->kardexProducto);
        

        for ($i=$indiceInicio; $i < count($rows); $i++) { 

            $operacionTrabajada = false; //true en compra o salida
            $fila = $rows[$i];
            $fechaCurrent = Carbon::parse($fila[$indiceColumnaFecha]);
            if(!$fechaCurrent>=$fechaAnterior){
                throw new Exception("La fecha " . $fechaCurrent->format('Y-m-d') . " no debe ser menor que la fecha anterior, error en la fila " . ($i + 1));
            }

            $entradaCantidad = (float)str_replace(',','',$fila[5]);
            $entradaCostoUnitario = (float)str_replace(',','',$fila[6]);
            $entradaCostoTotal = (float)str_replace(',','',$fila[7]);

            $salidaCantidad = (float)str_replace(',','',$fila[8]);
            $salidaLote = $fila[9];
            $salidaCostoUnitario = (float)str_replace(',','',$fila[10]);
            $salidaCostoTotal = (float)str_replace(',','',$fila[11]);

            if($i==$indiceInicio){
                
                if(!$this->validarCostoUnitario($entradaCantidad,$entradaCostoUnitario,$entradaCostoTotal)){
                    throw new Exception("El saldo inicial no coincide:" . round($entradaCostoTotal/$entradaCantidad,2) . " es diferente de " . round($entradaCostoUnitario,2));
                }
                
                $this->kardexProducto->stock_inicial = $entradaCantidad;
                $this->kardexProducto->costo_unitario = $entradaCostoUnitario;
                $this->kardexProducto->costo_total = $entradaCostoTotal;
                $this->kardexProducto->save();
                $operacionTrabajada = true;
            }else{
                
                $tabla10 = trim($fila[1]);
                $serie = trim($fila[2]);
                $numero = trim($fila[3]);
                $tipoOperacion = trim($fila[4]);

                if((int)$fila[$indiceColumnaTabla12]==2 && $tabla10 && $serie && $numero && $tipoOperacion && $entradaCantidad && $entradaCostoUnitario && $entradaCostoTotal){
                    //COMPRA
                    if(!$this->validarCostoUnitario($entradaCantidad,$entradaCostoUnitario,$entradaCostoTotal)){
                        throw new Exception("Valores de Compra Inválidos en la fila: " . ($i+1) . ", " . round($entradaCostoTotal/$entradaCantidad,2) . " es diferente de " . round($entradaCostoUnitario,2));
                    }
                    $operacionTrabajada = $this->registrarCompra($fechaCurrent, $tabla10,$serie,$numero,$tipoOperacion,$entradaCantidad,$entradaCostoUnitario,$entradaCostoTotal);
                }

            }
            if((int)$fila[$indiceColumnaTabla12]==10 && $salidaCantidad && $salidaLote && $salidaCostoUnitario && $salidaCostoTotal){
                //SALIDA A PRODUCCION
                if(!$this->validarCostoUnitario($salidaCantidad,$salidaCostoUnitario,$salidaCostoTotal)){
                    throw new Exception("Valores de Salida Inválidos en la fila: " . ($i+1) . ", " . round($salidaCostoTotal/$salidaCantidad,2) . " es diferente de " . round($salidaCostoUnitario,2));
                }

                $data = [
                    //'item',
                    'producto_id' => $this->productoId,
                    'campo_nombre'=>$salidaLote,
                    'cantidad'=>$salidaCantidad,
                    'fecha_reporte'=>$fechaCurrent->format('Y-m-d'),
                    //'compra_producto_id',
                    'costo_por_kg'=>$salidaCostoUnitario,
                    'total_costo'=>$salidaCostoTotal
                ];
                AlmacenServicio::registrarSalida($data,$this->kardexProducto);

                //$operacionTrabajada = $this->registrarSalida($fechaCurrent,$salidaCantidad,$salidaLote,$salidaCostoUnitario,$salidaCostoTotal);
            }
            /*
            if(!$operacionTrabajada && ){
                throw new Exception("No se trabajó ninguna operación en la fila: " . ($i+1) . ".");
            }*/

        }
        $this->file = null;
        $this->dispatch('importacionRealizada');
        $this->alert("success","Registros Importados Correctamente.");
    
    }
    private function registrarCompra($fecha,$tabla10,$serie,$numero,$tipoOperacion,$entradaCantidad,$entradaCostoUnitario,$entradaCostoTotal){
        $data = [
            'producto_id'=>$this->productoId,
            'tienda_comercial_id'=>null,
            'fecha_compra'=>$fecha->format('Y-m-d'),
            //'orden_compra',
            //'factura',
            'costo_por_kg'=>$entradaCostoUnitario,
            'total'=>$entradaCostoTotal,
            'stock'=>$entradaCantidad,
            //'estado',
            
            'tipo_compra_codigo'=>$tabla10,
            'serie'=>$serie,
            'numero'=>$numero,
            'tabla12_tipo_operacion'=>$tipoOperacion,
            'tipo_kardex'=>$this->kardex->tipo_kardex,
            
        ];
        ProductoServicio::registrarCompra($data);
        return true;
    }
    private function registrarSalida($fecha,$salidaCantidad,$salidaLote,$salidaCostoUnitario,$salidaCostoTotal){
        $data = [
            //'item',
            'producto_id' => $this->productoId,
            'campo_nombre'=>$salidaLote,
            'cantidad'=>$salidaCantidad,
            'fecha_reporte'=>$fecha->format('Y-m-d'),
            //'compra_producto_id',
            'costo_por_kg'=>$salidaCostoUnitario,
            'total_costo'=>$salidaCostoTotal
        ];
        AlmacenServicio::registrarSalida($data);
        return true;
    }
    private function validarCostoUnitario($cantidad,$precioUnitario,$costoTotal){
        return round($costoTotal/$cantidad,2) == round($precioUnitario,2);   
    }
    public function render()
    {
        return view('livewire.kardex-detalle-import-export-component');
    }
}
