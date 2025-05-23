<?php

namespace App\Livewire;

use App\Exports\KardexProductoExport;
use App\Models\Campo;
use App\Models\Kardex;
use App\Models\KardexProducto;
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
    public $kardexId;
    public function mount($productoid, $kardexId = null)
    {
        $this->productoId = $productoid;
        if ($kardexId) {
            $this->kardexId = $kardexId;
        }
    }

    public function updatedFileNegroDesdeKardex()
    {
        $this->procesarArchivo($this->fileNegroDesdeKardex, 'negro', $this->kardexId);
    }

    public function updatedFileBlancoDesdeKardex()
    {
        $this->procesarArchivo($this->fileBlancoDesdeKardex, 'blanco', $this->kardexId);
    }

    private function procesarArchivo($file, $tipo, $kardexId = null)
    {
        if ($file) {

            try {

                $codigoExistencia = null;

                if ($kardexId) {
                    //si no hay kardexid debe ser porque se esta usando en almacen, y alli no se necesita saber el kardex,
                    //se asume que si se usa alli se usara un excel generico con una sola hoja
                    $kardexProducto = KardexProducto::where('kardex_id', $kardexId)
                        ->where('producto_id', $this->productoId)
                        ->where('tipo_kardex', $tipo)
                        ->first();
                    if (!$kardexProducto) {
                        return $this->alert('error', 'El producto no existe en el Kardex, debe registrar como minimo su codigo de exixtencia.');
                    }
                    $codigoExistencia = $kardexProducto->codigo_existencia;
                }

                $spreadsheet = IOFactory::load($file->getRealPath());
                $response = $this->procesarKardexSheet($spreadsheet, $tipo, $kardexId, $codigoExistencia);

                $this->fileDesdeKardex = null;
                $filasAfectadasCompras = $response['filasAfectadasCompras'] ?? 0;
                $filasAfectadasAlmacen = $response['filasAfectadasAlmacen'] ?? 0;

                $this->dispatch('actualizarCompraProductos', [
                    'compras' => $filasAfectadasCompras,
                    'almacen' => $filasAfectadasAlmacen

                ]);
                //$this->alert("success", "Registros Importados Correctamente, ($filasAfectadasCompras) compras y {$filasAfectadasAlmacen} registros de salida.");

            } catch (Exception $th) {

                $this->alert('error', 'Error en Procesar Archivo:' . $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
        }
    }
    //Formato de codigo oficial
    protected function procesarKardexSheet($spreadsheet, $tipoKardex, $kardexId = null, $codigoExistencia = null)
    {
        $sheet = null;

        if ($codigoExistencia !== null) {
            // Buscar la hoja por nombre
            $sheet = $spreadsheet->getSheetByName($codigoExistencia);

            // Si no existe la hoja con ese nombre, lanzar una excepción o usar la hoja activa como fallback
            if (!$sheet) {
                throw new Exception("No se encontró la hoja con el nombre: $codigoExistencia");
                // O usar fallback:
                // $sheet = $spreadsheet->getActiveSheet();
            }
        } else {
            // Usar la primera hoja (o la activa)
            $sheet = $spreadsheet->getSheet(0) ?? $spreadsheet->getActiveSheet();
        }

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

        $tieneSaldoInicial = true;

        if ((int) $rows[$indiceInicio][$indiceColumnaTabla12] != 16) {
            $tieneSaldoInicial = false;
            //throw new Exception("El archivo no tiene el formato correcto, la celda E17 debe tener el codigo 16: SALDO INICIAL");
        }

        $campos = Campo::all();
        $mapaAliasANombre = [];

        // Construir el mapa: alias => nombre_real
        foreach ($campos as $campo) {
            $mapaAliasANombre[$campo->nombre] = $campo->nombre; // Incluir el nombre directo

            if (!empty($campo->alias)) {
                $aliasArray = array_map('trim', explode(',', $campo->alias));
                foreach ($aliasArray as $alias) {
                    $mapaAliasANombre[$alias] = $campo->nombre;
                }
            }
        }


        if ($kardexId) {
            $kardex = Kardex::find($kardexId);
            if ($kardex) {
                $fechaMinima = Carbon::parse($kardex->fecha_inicial);
                $fechaMaxima = $kardex->fecha_final ? Carbon::parse($kardex->fecha_final) : null;


                foreach ($campos as $campo) {
                    $valoresValidos[] = $campo->nombre;

                    if (!empty($campo->alias)) {
                        // Dividir los alias por coma, quitar espacios y agregarlos al array
                        $aliasArray = array_map('trim', explode(',', $campo->alias));
                        $valoresValidos = array_merge($valoresValidos, $aliasArray);
                    }
                }
                
                $camposFaltantes = [];

                for ($x = $indiceInicio; $x < count($rows); $x++) {
                    $fila = $x + 1;
                    if ($x == $indiceInicio && $tieneSaldoInicial) {
                        continue; // Saltar la primera fila si es cabecera
                    }

                    $valorCeldaCampo = trim($sheet->getCell('J' . ($x + 1))->getValue());
                    if ($valorCeldaCampo !== '' && !in_array($valorCeldaCampo, $valoresValidos)) {
                        $camposFaltantes[] = $valorCeldaCampo;
                    }

                    $valorCeldaFecha = $sheet->getCell('A' . ($x + 1))->getValue();

                    if (!$valorCeldaFecha) {
                        continue;
                    }

                    if (is_numeric($valorCeldaFecha)) {
                        $fechaCurrent = Carbon::parse(Date::excelToDateTimeObject($valorCeldaFecha));
                    } else {
                        $fechaCurrent = Carbon::parse($valorCeldaFecha);
                    }

                    if (!$fechaCurrent) {
                        continue;
                    }

                    // ⚠️ **Si alguna fecha está fuera del rango, lanzamos un error antes de procesar datos**
                    if ($fechaCurrent->lessThan($fechaMinima) || ($fechaMaxima && $fechaCurrent->greaterThan($fechaMaxima))) {
                        throw new Exception("Error en la fila {$x}: La fecha {$fechaCurrent->toDateString()} está fuera del rango permitido por este kardex: ({$fechaMinima->toDateString()} - " . ($fechaMaxima ? $fechaMaxima->toDateString() : "Sin límite") . ").");
                    }
                }

                if (!empty($camposFaltantes)) {
                    $faltantes = implode(', ', array_unique($camposFaltantes));
                    throw new Exception("Error: Los siguientes campos no existen en la base de datos: {$faltantes}. Modifique el Excel o los campos del sistema.");
                }
            }
        }

        $data = [];
        $dataAlmacen = [];

        for ($i = $indiceInicio; $i < count($rows); $i++) {
            try {
                $fila = $rows[$i];

                //$entradaCantidad = (float) str_replace(',', '', $fila[5]);
                $entradaCantidad = (float) $sheet->getCell('F' . ($i + 1))->getCalculatedValue(); //Esta tecnica permite obtener el valor total calculado sin importar el formato de la celda obtenida en excel
                $entradaCostoTotal = (float) $sheet->getCell('H' . ($i + 1))->getCalculatedValue();


                $tipoOperacion = trim($fila[$indiceColumnaTabla12]);

                $valorCeldaFecha = $sheet->getCell('A' . ($i + 1))->getValue();
                $fechaCurrent = null;

                if ($i == $indiceInicio && $tieneSaldoInicial) {
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
                $salidaCantidad = (float) $sheet->getCell('I' . ($i + 1))->getCalculatedValue();
                $salidaCostoUnitario = (float) $sheet->getCell('K' . ($i + 1))->getCalculatedValue();
                $salidaCostoTotal = (float) $sheet->getCell('L' . ($i + 1))->getCalculatedValue();
                $salidaLote = trim($sheet->getCell('J' . ($i + 1))->getValue());
                
                if (array_key_exists($salidaLote, $mapaAliasANombre)) {
                    $salidaLote = $mapaAliasANombre[$salidaLote];
                    
                }



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
