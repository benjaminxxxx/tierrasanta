<?php

namespace App\Livewire;

use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use DB;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AlmacenSalidaImportExportComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    public $productoId;
    public $fileDesdeKardex;
    public function mount($productoid)
    {
        $this->productoId = $productoid;
    }
    public function updatedFileDesdeKardex()
    {
        if ($this->fileDesdeKardex) {

            try {

                $this->validate([
                    'fileDesdeKardex' => 'required|file|mimes:xlsx,xls,csv',
                ]);

                $spreadsheet = IOFactory::load($this->fileDesdeKardex->getRealPath());

                $this->procesarKardexSheet($spreadsheet);

                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('KardexImportado');
            } catch (Exception $th) {

                $this->alert('error', 'Error en Procesar Archivo:' . $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
        }
    }
    protected function procesarKardexSheet($spreadsheet)
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

        for ($i = $indiceInicio; $i < count($rows); $i++) {
            try {
                $fila = $rows[$i];

                $salidaCantidad = (float) str_replace(',', '', $fila[8]);
                $salidaLote = $fila[9];
                $salidaCostoUnitario = (float) str_replace(',', '', $fila[10]);
                $salidaCostoTotal = (float) str_replace(',', '', $fila[11]);
                $tipoOperacion = trim($fila[$indiceColumnaTabla12]);
               
                $valorCeldaFecha = $sheet->getCell('A' . ($i+1))->getValue();
                $fechaCurrent = null;

                if (is_numeric($fila[0])) {
                    // Convertir número de Excel a fecha real

                    $fechaCurrent = Carbon::instance(Date::excelToDateTimeObject($fila[0]));
                } else {
                    // Convertir texto a fecha
                    $fechaCurrent = Carbon::parse($fila[0]);
                }

                if ($i == $indiceInicio) {
                    continue;
                }

                if (!$salidaCantidad || !$salidaLote || !$salidaCostoUnitario || !$salidaCostoTotal) {
                    continue;
                }

                if (is_numeric($valorCeldaFecha)) {
                    // Convertir número de Excel a fecha real
                    $fechaCurrent = Carbon::parse(Date::excelToDateTimeObject($valorCeldaFecha));
                } else {
                    // Convertir texto a fecha
                    $fechaCurrent = Carbon::parse($valorCeldaFecha);
                }
                if(!$fechaCurrent){
                    continue;
                }

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


                $data[] = [
                    'producto_id' => $this->productoId,
                    'campo_nombre' => $salidaLote,
                    'cantidad' => $salidaCantidad,
                    'fecha_reporte' => $fechaCurrent->format('Y-m-d'),
                    'maquinaria_id' => $maquinaria_id
                ];
            } catch (\Throwable $th) {
                throw new Exception('Error en la fila: ' . ($i + 1) . ': ' . $th->getMessage());
            }
        }
        
        AlmacenServicio::registrarSalida($data);

        $this->fileDesdeKardex = null;
        $this->dispatch('ActualizarProductos');
        $this->alert("success", "Registros Importados Correctamente.");
    }
    public function render()
    {
        return view('livewire.almacen-salida-import-export-component');
    }
}
