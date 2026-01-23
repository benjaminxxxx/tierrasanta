<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\DistribucionCombustible;
use App\Models\RptDistribucionCombustible;
use App\Services\AlmacenServicio;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DistribucionCombustibleComponent extends Component
{
    use LivewireAlert;

    public $fecha, $campo, $horaInicio, $horaFin, $descripcion;
    public $mostrarFormulario = false;
    public $campos;
    public $mes;
    public $anio;
    public $listaSalidas = [];
    public $listaDistribuciones = [];
    public $almacenProductoSalidaId;
    public $maquinaria;
    public $tipoKardex;
    protected $listeners = ['verDistribucionCombustublble', 'calcularDistribucion'];

    public function mount()
    {
        $this->campos = Campo::listar();
    }
    public function calcularDistribucion($tipo, $mes, $anio)
    {
        try {
            $spreadsheet = ExcelHelper::cargarPlantilla('reporte_almacen_combustible_distribucion.xlsx');
            $hoja = $spreadsheet->getSheetByName('DISTRIBUCION');

            if (!$hoja) {
                throw new Exception("No se ha configurado un formato para el documento a exportar.");
            }

            $nuevoNombre = mb_strtoupper(Str::slug('DISTRIBUCION_' . $anio . '_' . $anio, '_')); // Reemplaza espacios con "_"
            $hoja->setTitle($nuevoNombre);

            $hoja->setCellValue("A1", "01/{$mes}/{$anio}");
            $hoja->getStyle("A1")
                ->getNumberFormat()
                ->setFormatCode('DD-MMM');

            $listaSalidas = AlmacenServicio::obtenerRegistrosPorFecha($mes, $anio, 'combustible', $tipo);


            $table = $hoja->getTableByName('DistribucionTable');

            if (!$table) {
                throw new Exception("La plantilla no tiene una tabla llamada DistribucionTable.");
            }

            $fila = ExcelHelper::primeraFila($table) + 1;

            foreach ($listaSalidas as $dato) {
                $carbonFecha = Carbon::parse($dato->fecha_reporte);
                $excelFecha = ExcelDate::PHPToExcel($carbonFecha);
                // 📌 Fila de salida principal (Total general)
                $hoja->setCellValue("A{$fila}", $excelFecha);
                $hoja->setCellValue("H{$fila}", $dato->cantidad); // Cantidad total distribuida
                $hoja->setCellValue("J{$fila}", $dato->maquinaria ? $dato->maquinaria->nombre : 'N/A');
                $hoja->setCellValue("K{$fila}", $dato->total_costo);

                $hoja->getStyle("A{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode('DD-MMM');

                // Aplicar formato para Salida

                $columnas = ["A", "H", "J", "K"];
                foreach ($columnas as $columna) {


                    // Aplicar formato a cada celda (si es necesario)
                    $hoja->getStyle("{$columna}{$fila}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FF0000'] // Rojo
                        ]
                    ]);
                }

                $columnasCentradas = ["A"];
                foreach ($columnasCentradas as $columnasCentrada) {
                    $hoja->getStyle("{$columnasCentrada}{$fila}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ]
                    ]);
                }

                // Guardamos la fila inicial para las distribuciones
                $filaInicio = $fila + 1;
                $filaSalida = $fila;
                $fila++;

                $distribuciones = count($dato['distribuciones']);
                $filaRango = $filaSalida + $distribuciones;

                foreach ($dato['distribuciones'] as $dist) {

                    $carbonFecha = Carbon::parse($dist->fecha);           // 2025-01-10 00:00:00 (ejemplo)
                    $carbonHoraInicio = Carbon::parse($dist->hora_inicio); // 14:05 (ejemplo)
                    $carbonHoraFin = Carbon::parse($dist->hora_salida);   // 16:30 (ejemplo)

                    $excelFecha = ExcelDate::PHPToExcel($carbonFecha);           // valor numérico
                    $excelHoraInicio = ExcelDate::PHPToExcel($carbonHoraInicio); // valor numérico
                    $excelHoraFin = ExcelDate::PHPToExcel($carbonHoraFin);       // valor numérico

                    $hoja->setCellValue("A{$fila}", $excelFecha);
                    $hoja->setCellValue("B{$fila}", $excelHoraInicio);
                    $hoja->setCellValue("C{$fila}", $excelHoraFin);

                    // Aplicar el formato deseado
                    $hoja->getStyle("A{$fila}")
                        ->getNumberFormat()
                        ->setFormatCode('DD-MMM'); // Ej: 10-Jan

                    $hoja->getStyle("B{$fila}")
                        ->getNumberFormat()
                        ->setFormatCode('h:mm AM/PM'); // Ej: 2:05 PM

                    $hoja->getStyle("C{$fila}")
                        ->getNumberFormat()
                        ->setFormatCode('h:mm AM/PM'); // Ej: 4:30 PM

                    // 📌 Insertar valores de la distribución

                    $hoja->setCellValue("D{$fila}", "=HOUR(C{$fila}-B{$fila})+MINUTE(C{$fila}-B{$fila})/60");
                    $hoja->getStyle("D{$fila}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00');

                    $hoja->setCellValue("E{$fila}", $dist->campo);
                    $hoja->setCellValue("F{$fila}", "=+\$H\${$filaSalida}*L{$fila}");
                    $hoja->setCellValue("G{$fila}", "=+F{$fila}*\$K\${$filaSalida}");
                    $hoja->setCellValue("I{$fila}", $dist->actividad);
                    $hoja->setCellValue("J{$fila}", $dist->maquinaria_nombre);



                    // 🧮 Ratio de distribución: =D6 / SUMA(D6:D8) (dentro de su grupo)
                    $hoja->setCellValue("L{$fila}", "=D{$fila}/SUM(D{$filaInicio}:D{$filaRango})");

                    // 📌 Calcular cantidad de combustible: =H6 * L8


                    // 💰 Valor del costo: =L6*K5 (Ratio * Costo total de la maquinaria)
                    $hoja->setCellValue("M{$fila}", "=+G{$fila}/D{$fila}");

                    // Aplicar formato para Distribución
                    $hoja->getStyle("A{$fila}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']], // Fondo amarillo
                    ]);
                    $hoja->getStyle("E{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']], // Negrita y negro
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCCFF']], // Fondo púrpura
                    ]);

                    $columnasCentradas = ["B", "C", "D"];
                    foreach ($columnasCentradas as $columnasCentrada) {
                        $hoja->getStyle("{$columnasCentrada}{$fila}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ]
                        ]);
                    }

                    $fila++;
                }
            }

            ExcelHelper::actualizarRangoTabla($table, $fila - 1);

            // 📁 Definir ruta de almacenamiento en "storage/app/public/reporte/YYYY-MM/"
            $folderPath = 'reporte/' . date('Y-m');
            $fileName = "Reporte_Distribucion_{$mes}_{$anio}_{$tipo}.xlsx";
            $filePath = "{$folderPath}/{$fileName}";

            // 📂 Crear carpeta si no existe
            Storage::disk('public')->makeDirectory($folderPath);

            // 💾 Guardar el archivo en storage/app/public/
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(true);
            $writer->save(Storage::disk('public')->path($filePath));

            if ($tipo == 'blanco') {
                RptDistribucionCombustible::updateOrCreate(
                    ['mes' => $mes, 'anio' => $anio],
                    ['file_blanco' => $filePath]
                );
            } else if ($tipo == 'negro') {
                RptDistribucionCombustible::updateOrCreate(
                    ['mes' => $mes, 'anio' => $anio],
                    ['file_negro' => $filePath]
                );
            }
            $this->dispatch('rptDistribucionesGeneradas');


        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Error: ' . $th->getMessage());
        }

    }
    public function guardarDistribucion()
    {
        $this->validate([
            'fecha' => 'required|date',
            'campo' => 'required|string',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'Ingrese una fecha válida.',
            'campo.required' => 'Debe seleccionar un campo.',
            'horaInicio.required' => 'La hora de inicio es obligatoria.',
            'horaInicio.date_format' => 'El formato de la hora de inicio no es válido.',
            'horaFin.required' => 'La hora de fin es obligatoria.',
            'horaFin.date_format' => 'El formato de la hora de fin no es válido.',
            'horaFin.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
        ]);

        try {
            // Extraer año y mes de la fecha ingresada
            $anio = date('Y', strtotime($this->fecha));
            $mes = date('m', strtotime($this->fecha));

            // Buscar la última salida de almacén dentro del mismo mes y anio, pero antes de la fecha dada
            $almacenSalida = AlmacenProductoSalida::whereYear('fecha_reporte', $anio)
                ->whereMonth('fecha_reporte', $mes)
                ->whereDate('fecha_reporte', '<=', $this->fecha)
                ->where('maquinaria_id', $this->maquinaria->id)
                ->orderBy('fecha_reporte', 'desc')
                ->first();

            // Si no hay salidas anteriores en el mismo mes y año, mostrar error
            if (!$almacenSalida) {
                return $this->alert('error', 'No hay una salida de almacén anterior dentro del mismo mes y año.');
            }

            // Crear la distribución de combustible
            DistribucionCombustible::create([
                'fecha' => $this->fecha,
                'campo' => $this->campo,
                'hora_inicio' => $this->horaInicio,
                'hora_salida' => $this->horaFin,
                'actividad' => $this->descripcion,
                'almacen_producto_salida_id' => $almacenSalida->id,
                'maquinaria_id' => $almacenSalida->maquinaria_id,
            ]);

            // Llamar función para generar cálculos adicionales
            $this->generarDistribucion();

            // Reiniciar los valores del formulario
            $this->reset(['fecha', 'campo', 'horaInicio', 'horaFin', 'descripcion']);
            $this->alert('success', 'Distribución guardada correctamente.');
        } catch (Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', $th->getMessage());
        }
    }

    public function verDistribucionCombustublble($salidaId, $mes, $anio)
    {
        $this->reset(['fecha', 'campo', 'horaInicio', 'horaFin', 'descripcion']);
        $this->mes = $mes;
        $this->anio = $anio;
        $this->almacenProductoSalidaId = $salidaId;

        $almacenProductoSalida = AlmacenProductoSalida::find($salidaId);
        if (!$almacenProductoSalida) {
            return $this->alert('error', 'La salida y no existe.');
        }

        $this->maquinaria = $almacenProductoSalida->maquinaria;
        $this->tipoKardex = $almacenProductoSalida->tipo_kardex;

        $this->generarDistribucion();
        $this->mostrarFormulario = true;
    }
    public function generarDistribucion()
    {

        $this->listaSalidas = AlmacenProductoSalida::with(['distribuciones', 'maquinaria', 'producto'])
            ->whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->where('maquinaria_id', $this->maquinaria->id)
            ->where('tipo_kardex', $this->tipoKardex)
            ->whereHas('producto', function ($q) {
                $q->where('categoria_codigo', 'combustible'); // Filtrar por categoría
            })
            ->where(function ($q) {
                $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''); // Detecta NULL y ''
            })
            ->get();

    }
    public function render()
    {
        return view('livewire.distribucion-combustible-component');
    }
}
