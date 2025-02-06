<?php

namespace App\Livewire;

use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\ProductoServicio;
use Carbon\Carbon;
use DB;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CompraProductoImportExportComponent extends Component
{
    use WithFileUploads;
    use LivewireAlert;
    public $productoId;
    public $fileNegroDesdeKardex;
    public $fileBlancoDesdeKardex;
    public function mount($productoid)
    {
        $this->productoId = $productoid;
    }
    public function updatedFileNegroDesdeKardex()
    {
        $this->procesarArchivo($this->fileNegroDesdeKardex, 'negro');
    }

    public function updatedFileBlancoDesdeKardex()
    {
        $this->procesarArchivo($this->fileBlancoDesdeKardex, 'blanco');
    }

    private function procesarArchivo($file, $tipo)
    {
        if ($file) {

            try {

                $spreadsheet = IOFactory::load($file->getRealPath());
                $response = $this->procesarKardexSheet($spreadsheet, $tipo);

                $this->fileDesdeKardex = null;
                $filasAfectadasCompras = $response['filasAfectadasCompras'] ?? 0;
                $filasAfectadasAlmacen = $response['filasAfectadasAlmacen'] ?? 0;
                $this->dispatch('actualizarCompraProductos');
                $this->alert("success", "Registros Importados Correctamente, ($filasAfectadasCompras) compras y {$filasAfectadasAlmacen} registros de salida.");

            } catch (Exception $th) {

                $this->alert('error', 'Error en Procesar Archivo:' . $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
        }
    }
    protected function procesarKardexSheet($spreadsheet, $tipoKardex)
    {
        $sheet = $spreadsheet->getSheet(0) ?? $spreadsheet->getActiveSheet();

        if (!$sheet) {
            throw new Exception("El Excel no tiene alguna hoja válida ");
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


        $data = [];
        $dataAlmacen = [];

        for ($i = $indiceInicio; $i < count($rows); $i++) {
            try {
                $fila = $rows[$i];

                $entradaCantidad = (float) str_replace(',', '', $fila[5]);
                $entradaCostoUnitario = (float) str_replace(',', '', $fila[6]);
                $entradaCostoTotal = (float) str_replace(',', '', $fila[7]);

                $tipoOperacion = trim($fila[$indiceColumnaTabla12]);

                $valorCeldaFecha = $sheet->getCell('A' . ($i + 1))->getValue();
                $fechaCurrent = null;

                if ($i == $indiceInicio) {
                    continue;
                }
                if (is_numeric($valorCeldaFecha)) {
                    // Convertir número de Excel a fecha real
                    $fechaCurrent = Carbon::parse(Date::excelToDateTimeObject($valorCeldaFecha));
                } else {
                    // Convertir texto a fecha
                    $fechaCurrent = Carbon::parse($valorCeldaFecha);
                }
                if (!$fechaCurrent) {
                    continue;
                }

                $tabla10 = trim($fila[1]);
                $serie = trim($fila[2]);
                $numero = trim($fila[3]);
                $tipoOperacion = trim($fila[$indiceColumnaTabla12]);

                if ($entradaCantidad > 0 && $entradaCostoTotal > 0) {
                    //COMPRA
                    if (!$tabla10) {
                        throw new Exception("Falta el tipo de compra tabla 10.");
                    }
                    if ((int) $tipoOperacion != 2) {
                        throw new Exception("Existen valores para una compra, pero el codigo registrado no es 2.");
                    }

                    $tipo_compra_codigo = isset($tabla10) ? str_pad($tabla10, 2, '0', STR_PAD_LEFT) : null;

                    $data[] = [
                        'producto_id' => $this->productoId,
                        'fecha_compra' => $fechaCurrent->format('Y-m-d'),
                        'costo_por_kg' => $entradaCostoTotal / $entradaCantidad,
                        'total' => $entradaCostoTotal,
                        'stock' => $entradaCantidad,
                        'tipo_compra_codigo' => $tipo_compra_codigo,
                        'serie' => $serie,
                        'numero' => $numero,
                        'tabla12_tipo_operacion' => $tipoOperacion,
                        'tipo_kardex' => $tipoKardex,
                        'estado' => 1
                    ];
                }

                /**
                 * SALIDAS
                 */
                $salidaCantidad = (float) str_replace(',', '', $fila[8]);
                $salidaLote = $fila[9];
                $salidaCostoUnitario = (float) str_replace(',', '', $fila[10]);
                $salidaCostoTotal = (float) str_replace(',', '', $fila[11]);

                if ($salidaCantidad > 0 && $salidaLote != '') {
                    if ((int) $tipoOperacion != 10) {
                        throw new Exception("Existen valores para una salida a producción, pero el codigo registrado esta vacio o no es 10.");
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

                    $dataAlmacen[] = [
                        'producto_id' => $this->productoId,
                        'campo_nombre' => $salidaLote,
                        'cantidad' => $salidaCantidad,
                        'fecha_reporte' => $fechaCurrent->format('Y-m-d'),
                        'maquinaria_id' => $maquinaria_id,
                        'tipo_kardex' => $tipoKardex,
                    ];
                }

            } catch (\Throwable $th) {
                throw new Exception('Error en la fila: ' . ($i) . ': ' . $th->getMessage());
            }
        }

        $filasAfectadasCompras = ProductoServicio::registrarCompraProducto($data);
        $filasAfectadasAlmacen = AlmacenServicio::registrarSalida($dataAlmacen);

        return [
            'filasAfectadasCompras' => $filasAfectadasCompras,
            'filasAfectadasAlmacen' => $filasAfectadasAlmacen,
        ];

    }
    public function render()
    {
        return view('livewire.compra-producto-import-export-component');
    }
}
