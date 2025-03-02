<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use Illuminate\Support\Facades\DB;
use App\Models\Kardex;
use App\Models\KardexProducto;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\ProductoServicio;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Storage;

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
    protected $listeners = ['procesarFile'];

    public function mount()
    {
        $this->kardex = Kardex::find($this->kardexId);
        $this->producto = Producto::find($this->productoId);
        $this->kardexProducto = KardexProducto::where('kardex_id', $this->kardexId)
            ->where('producto_id', $this->productoId)
            ->first();
    }

    public function procesarFile($filePath)
    {

        try {
            $fullPath = Storage::disk('public')->path($filePath);

            // Carga el archivo Excel
            $spreadsheet = IOFactory::load($fullPath);

            $this->procesarKardexSheet($spreadsheet);

            $this->alert('success', 'Los datos se importaron correctamente.');
            $this->dispatch('KardexImportado');
        } catch (Exception $e) {
            //$this->alert('error', $e->getMessage());
            $this->alert('error', 'Error en Procesar Archivo:' . $e->getMessage(), [

                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
        }
    }
    public function updatedFile()
    {
        if ($this->file) {

            try {

                $this->validate([
                    'file' => 'required|file|mimes:xlsx,xls,csv',
                ]);

                $spreadsheet = IOFactory::load($this->file->getRealPath());

                $this->procesarKardexSheet($spreadsheet);

                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('KardexImportado');
            } catch (Exception $e) {
                //$this->alert('error', $e->getMessage());
                $this->alert('error', 'Error al cargar el archivo: ' . $e->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
        }
    }
    protected function procesarKardexSheet($spreadsheet)
    {

        if (!$this->kardex) {
            throw new Exception("El kardex no es válido.");
        }
        if (!$this->producto) {
            throw new Exception("El producto no existe.");
        }
        if (!$this->kardexProducto) {
            throw new Exception("El kardex del producto no es válido.");
        }
        if (trim($this->kardexProducto->codigo_existencia) == '') {
            throw new Exception("El producto no tiene un código de existencia válido, debe actualizar la información.");
        }

        $sheet = $spreadsheet->getSheetByName($this->kardexProducto->codigo_existencia);


        if (!$sheet) {
            throw new Exception("El Excel no tiene una hoja llamada: " . $this->kardexProducto->codigo_existencia);
        }

        $rows = $sheet->toArray();


        $indiceInicio = 16;
        $indiceColumnaFecha = 0;
        $indiceColumnaTabla12 = 4;

        if (!isset($rows[$indiceInicio])) {
            throw new Exception("El archivo no tiene el formato correcto, la información debe iniciar en la fila: " . ($indiceInicio + 1));
        }
        if (!isset($rows[$indiceInicio][$indiceColumnaFecha])) {
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($indiceColumnaFecha + 1) . " para fechas");
        }
        if (!isset($rows[$indiceInicio][$indiceColumnaTabla12])) {
            throw new Exception("El archivo no tiene el formato correcto, no existe la columna " . ($indiceColumnaTabla12 + 1));
        }

        if ((int) $rows[$indiceInicio][$indiceColumnaTabla12] != 16) {
            throw new Exception("El archivo no tiene el formato correcto, la celda E17 debe tener el codigo 16: SALDO INICIAL");
        }

        //formato 1/1/2024 o 01/01/2024
        $fecha = Carbon::parse($rows[$indiceInicio][$indiceColumnaFecha]);
        if (!$fecha->isValid()) {
            throw new Exception("La fecha " . $rows[$indiceInicio][$indiceColumnaFecha] . " no es válida");
        }

        $fechaKardex = Carbon::parse($this->kardex->fecha_inicial)->format('Y-m-d');
        if ($fechaKardex != $fecha->format('Y-m-d')) {
            throw new Exception("La fecha " . $fechaKardex . " no coindice con la fecha inicial del Kardex: " . $fecha->format('Y-m-d'));
        }

        $fechaAnterior = Carbon::parse($this->kardex->fecha_inicial);

        AlmacenServicio::resetearStocks($this->kardexProducto);
      

        $kardexData = array_slice($rows, $indiceInicio);
        
        // Convertir las fechas al formato de Carbon para ordenar
        foreach ($kardexData as &$row) {
            $row[0] = Carbon::parse($row[0] ?? null);
        }

        // Ordenar los registros
        usort($kardexData, function ($a, $b) {
            // Comparar fechas
            if ($a[0] != $b[0]) {
                return $a[0] <=> $b[0];
            }

            // Si las fechas son iguales, priorizar apertura (índice 4 = 16)
            if ($a[4] == '16') {
                return -1;
            }
            if ($b[4] == '16') {
                return 1;
            }

            // Luego priorizar compra (índice 4 = 2)
            if ($a[4] == '2') {
                return -1;
            }
            if ($b[4] == '2') {
                return 1;
            }

            // Finalmente, priorizar salida a producción (índice 4 = 10)
            if ($a[4] == '10') {
                return 1;
            }
            if ($b[4] == '10') {
                return -1;
            }

            // Mantener el orden para otros casos
            return 0;
        });

        // Restaurar el formato de fecha original
        foreach ($kardexData as &$row) {
            $row[0] = $row[0]->format('d/m/Y');
        }

        for ($i = $indiceInicio; $i < count($rows); $i++) {

            try {
                $operacionTrabajada = false; //true en compra o salida

                $fila = $rows[$i];

                $fechaCurrent = Carbon::parse($fila[$indiceColumnaFecha]);
                if (!$fechaCurrent >= $fechaAnterior) {
                    throw new Exception("La fecha " . $fechaCurrent->format('Y-m-d') . " no debe ser menor que la fecha anterior, error en la fila " . ($i + 1));
                }

                $entradaCantidad = (float) str_replace(',', '', $fila[5]);
                $entradaCostoUnitario = (float) str_replace(',', '', $fila[6]);
                $entradaCostoTotal = (float) str_replace(',', '', $fila[7]);

                $salidaCantidad = (float) str_replace(',', '', $fila[8]);
                $salidaLote = $fila[9];
                $salidaCostoUnitario = (float) str_replace(',', '', $fila[10]);
                $salidaCostoTotal = (float) str_replace(',', '', $fila[11]);

                if ($i == $indiceInicio) {

                    if (!$this->validarCostoUnitario($entradaCantidad, $entradaCostoUnitario, $entradaCostoTotal)) {
                        throw new Exception("El saldo inicial no coincide:" . round($entradaCostoTotal / $entradaCantidad, 3) . " es diferente de " . round($entradaCostoUnitario, 3));
                    }

                    $this->kardexProducto->stock_inicial = $entradaCantidad;
                    $this->kardexProducto->costo_unitario = $entradaCostoUnitario;
                    $this->kardexProducto->costo_total = $entradaCostoTotal;
                    $this->kardexProducto->save();
                    $operacionTrabajada = true;
                } else {



                    $tabla10 = trim($fila[1]);
                    $serie = trim($fila[2]);
                    $numero = trim($fila[3]);
                    $tipoOperacion = trim($fila[$indiceColumnaTabla12]);

                    if ($serie && $numero && $entradaCantidad && $entradaCostoUnitario && $entradaCostoTotal) {
                        //COMPRA
                        if(!$tabla10){
                            throw new Exception("Falta el tipo de compra tabla 10.");
                        }
                        if((int) $tipoOperacion != 2){
                            throw new Exception("Existen valores para una compra, pero el codigo registrado no es 2.");
                        }

                        if (!$this->validarCostoUnitario($entradaCantidad, $entradaCostoUnitario, $entradaCostoTotal)) {
                            throw new Exception("Valores de Compra Inválidos en la fila: " . ($i + 1) . ", " . round($entradaCostoTotal / $entradaCantidad, 2) . " es diferente de " . round($entradaCostoUnitario, 2));
                        }


                        $this->registrarCompra($fechaCurrent, $tabla10, $serie, $numero, $tipoOperacion, $entradaCantidad, $entradaCostoUnitario, $entradaCostoTotal);
                    }
                }

                $tipoOperacion = trim($fila[$indiceColumnaTabla12]);


                if ($salidaCantidad && $salidaLote && $salidaCostoUnitario && $salidaCostoTotal) {
                    if((int) $tipoOperacion != 10){
                        throw new Exception("Existen valores para una salida a producción, pero el codigo registrado esta vacio o no es 10.");
                    }
                    if (!$this->validarCostoUnitario($salidaCantidad, $salidaCostoUnitario, $salidaCostoTotal)) {
                        throw new Exception("Valores de Salida Inválidos en la fila: " . ($i + 1) . ", " . round($salidaCostoTotal / $salidaCantidad, 2) . " es diferente de " . round($salidaCostoUnitario, 2));
                    }

                    $esCombustible = Producto::esCombustible($this->productoId);

                    $maquinaria_id = null;
                    if ($esCombustible) {

                        $maquinaria = Maquinaria::where(DB::raw('LOWER(nombre) COLLATE utf8mb4_general_ci'), strtolower($salidaLote))
                            ->orWhere(DB::raw('LOWER(alias_blanco) COLLATE utf8mb4_general_ci'), strtolower($salidaLote))
                            ->first();

                        if ($maquinaria) {
                            $maquinaria_id = $maquinaria->id;
                        } else {
                            throw new Exception("No existe una Maquinaria con el nombre o alias: " . $salidaLote);
                        }
                        $salidaLote = '';
                    }


                    $data = [
                        'producto_id' => $this->productoId,
                        'campo_nombre' => $salidaLote,
                        'cantidad' => $salidaCantidad,
                        'fecha_reporte' => $fechaCurrent->format('Y-m-d'),
                        'costo_por_kg' => $salidaCostoUnitario,
                        'total_costo' => $salidaCostoTotal,
                        'maquinaria_id' => $maquinaria_id
                    ];

                    AlmacenServicio::registrarSalida($data, $this->kardexProducto);
                }
            } catch (\Throwable $th) {
                throw new Exception('Error en la fila: ' . ($i+1) . ': ' . $th->getMessage());
            }
        }

        $this->file = null;
        $this->dispatch('importacionRealizada');
        $this->alert("success", "Registros Importados Correctamente.");
    }
    private function registrarCompra($fecha, $tabla10, $serie, $numero, $tipoOperacion, $entradaCantidad, $entradaCostoUnitario, $entradaCostoTotal)
    {

        try {
            $data = [
                'producto_id' => $this->productoId,
                'tienda_comercial_id' => null,
                'fecha_compra' => $fecha->format('Y-m-d'),
                'costo_por_kg' => $entradaCostoUnitario,
                'total' => $entradaCostoTotal,
                'stock' => $entradaCantidad,
                'tipo_compra_codigo' => $tabla10,
                'serie' => $serie,
                'numero' => $numero,
                'tabla12_tipo_operacion' => $tipoOperacion,
                'tipo_kardex' => $this->kardex->tipo_kardex,
            ];

            ProductoServicio::registrarCompra($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
  
    private function validarCostoUnitario($cantidad, $precioUnitario, $costoTotal)
    {
        if ($cantidad > 0) {
            return $this->validarConPrecision($cantidad, $precioUnitario, $costoTotal, 4);
        }
        return true;
    }
    private function validarConPrecision($cantidad, $precioUnitario, $costoTotal, $intentosMaximos)
    {
        for ($precision = 1; $precision <= $intentosMaximos; $precision++) {
            if (round($costoTotal / $cantidad, $precision) == round($precioUnitario, $precision)) {
                return true;
            }
        }
        return false;
    }

    public function render()
    {
        return view('livewire.kardex-detalle-import-export-component');
    }
}
