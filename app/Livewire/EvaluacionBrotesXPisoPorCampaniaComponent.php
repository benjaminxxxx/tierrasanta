<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\Empleado;
use App\Models\EvaluacionBrotesXPiso;
use App\Models\EvaluacionBrotesXPisoDetalle;
use App\Support\CalculoHelper;
use App\Support\ExcelHelper;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EvaluacionBrotesXPisoPorCampaniaComponent extends Component
{
    #region TRAITS
    use LivewireAlert;
    #endregion

    #region VARIABLES
    public $campaniaId;
    public $campania;
    public $evaluacionesBrotesXPiso = [];
    //VARIABLES FORM CREAR EDITAR
    public $mostrarFormulario = false;
    public $evaluadorSeleccionado;
    public $evaluador;
    public $evaluadores = [];
    public $metros_cama;
    public $fecha;
    public $idTable;
    public $listaBroteXPlantaDetalle = [];
    public $evaluacionBrotesXPisoId;
    public $fileNameReporteBroteXPiso = "EVALUACION BROTE X PISO";
    public $evaluacionBrotesXPiso;
    protected $listeners = ['storeTableDataBrotesXPiso', 'confirmareliminarBrotesXPiso'];
    #endregion

    #region MOUNT
    public function mount($campaniaId)
    {
        $this->campania = CampoCampania::find($campaniaId);
        if ($this->campania) {
            $this->campaniaId = $campaniaId;
            $this->obtenerEvaluacionesBroteXPiso();
        }
        //inicializacion para form crear editar
        $this->idTable = "table" . Str::random(15);
    }
    #endregion

    #region PANEL PRINCIPAL
    public function obtenerEvaluacionesBroteXPiso()
    {
        if (!$this->campaniaId) {
            $this->evaluacionesBrotesXPiso = [];
            return;
        }
        $this->evaluacionesBrotesXPiso = EvaluacionBrotesXPiso::where('campania_id', $this->campaniaId)
            ->orderBy('fecha', 'asc')->get();
    }
    public function duplicar($evaluacionBrotesXPisoId)
    {
        try {
            // Buscar la evaluación original
            $evaluacionOriginal = EvaluacionBrotesXPiso::with('detalles')->find($evaluacionBrotesXPisoId);

            if (!$evaluacionOriginal) {
                return;
            }

            // Crear la nueva evaluación duplicada con la fecha actual
            $nuevaEvaluacion = $evaluacionOriginal->replicate();
            $nuevaEvaluacion->fecha = now(); // Asignar la fecha actual
            $nuevaEvaluacion->save();

            // Duplicar los detalles
            foreach ($evaluacionOriginal->detalles as $detalle) {
                $nuevoDetalle = $detalle->replicate();
                $nuevoDetalle->brotes_x_piso_id = $nuevaEvaluacion->id; // Asignar la nueva evaluación
                $nuevoDetalle->save();
            }

            $this->alert('success', 'Evaluación duplicada con éxito');
            $this->obtenerEvaluacionesBroteXPiso();
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error al intentar duplicar el registro');
        }
    }

    public function eliminarBrotesXPiso($evaluacionBrotesXPisoId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmareliminarBrotesXPiso',
            'data' => [
                'evaluacionBrotesXPisoId' => $evaluacionBrotesXPisoId,
            ],
        ]);
    }
    public function confirmareliminarBrotesXPiso($data)
    {
        try {
            $evaluacionBrotesXPisoId = $data['evaluacionBrotesXPisoId'];
            $evaluacionBrotesXPiso = EvaluacionBrotesXPiso::findOrFail($evaluacionBrotesXPisoId);
            $evaluacionBrotesXPiso->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->obtenerEvaluacionesBroteXPiso();

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            return $this->alert('error', $th->getMessage());
        }
    }
    #endregion

    #region FORM CREAR EDITAR
    public function editarEvaluacionBrotesPorPiso($evaluacionBrotesXPisoId)
    {
        try {
            $this->resetForm();
            $evaluacionBrotesXPiso = EvaluacionBrotesXPiso::findOrFail($evaluacionBrotesXPisoId);

            $this->evaluacionBrotesXPisoId = $evaluacionBrotesXPiso->id;
            $this->fecha = $evaluacionBrotesXPiso->fecha;
            $this->metros_cama = $evaluacionBrotesXPiso->metros_cama;

            // Si existe el evaluador, asignarlo
            $this->evaluadorSeleccionado = [
                'nombre' => $evaluacionBrotesXPiso->evaluador,
                'id' => $evaluacionBrotesXPiso->empleado_id
            ];

            $this->evaluacionBrotesXPiso = $evaluacionBrotesXPiso;

            $this->listaBroteXPlantaDetalle = $evaluacionBrotesXPiso->detalles->toArray();
            $this->dispatch('cargarDataBrotesXPiso', $this->listaBroteXPlantaDetalle);

            $this->mostrarFormulario = true;

        } catch (\Throwable $th) {
            $this->evaluacionBrotesXPiso = null;
            $this->alert('error', 'El registro ya no existe.');
        }
    }
    public function storeTableDataBrotesXPiso($datos)
    {
        $this->validate([
            'metros_cama' => 'required|numeric|min:0|max:99999.999',
            'evaluadorSeleccionado.nombre' => 'required|string',
            'evaluadorSeleccionado.id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date',
        ], [
            'metros_cama.required' => 'Los metros de cama son obligatorios.',
            'metros_cama.numeric' => 'Los metros de cama deben ser un número.',
            'metros_cama.max' => 'El número es demasiado grande, maximo 5 digitos y 3 decimales.',
            'evaluadorSeleccionado.nombre.required' => 'Debe seleccionar un evaluador.',
            'evaluadorSeleccionado.id.required' => 'Debe proporcionar un ID de evaluador.',
            'evaluadorSeleccionado.id.exists' => 'El evaluador seleccionado no es válido.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
        ]);

        try {
            // Construcción del array de datos
            $data = [
                'metros_cama' => $this->metros_cama,
                'evaluador' => $this->evaluadorSeleccionado['nombre'],
                'empleado_id' => $this->evaluadorSeleccionado['id'],
                'fecha' => $this->fecha,
                'campania_id' => $this->campania->id
            ];

            $message = '';
            $evaluacionBrotesXPiso = null;

            $reporteDetalle = $this->generarReporteDetalle($datos);
            $data['reporte_file'] = $reporteDetalle['file'];

            if ($this->evaluacionBrotesXPisoId) {
                // Si existe, actualiza el registro
                $evaluacionBrotesXPiso = EvaluacionBrotesXPiso::where('id', $this->evaluacionBrotesXPisoId)->first();
                if ($evaluacionBrotesXPiso) {
                    $evaluacionBrotesXPiso->update($data);
                }

                $message = 'Registro actualizado correctamente.';
            } else {
                // Si no existe, inserta un nuevo registro
                $evaluacionBrotesXPiso = EvaluacionBrotesXPiso::create($data);
                $this->evaluacionBrotesXPisoId = $evaluacionBrotesXPiso->id;
                $message = 'Registro exitoso de Brotes por Piso.';
            }

            if ($evaluacionBrotesXPiso && $this->evaluacionBrotesXPisoId) {

                if ($data['reporte_file']) {
                    $this->actualizarDetalle($data['reporte_file']);

                    $evaluacionBrotesXPiso = $evaluacionBrotesXPiso->fresh('detalles');

                    $this->listaBroteXPlantaDetalle = $evaluacionBrotesXPiso->detalles->toArray();
                    $this->evaluacionBrotesXPiso = $evaluacionBrotesXPiso;
                    $this->dispatch('cargarDataBrotesXPiso', $this->listaBroteXPlantaDetalle);
                }

                $this->obtenerEvaluacionesBroteXPiso();
            }

            $this->alert('success', $message);
            $this->dispatch('poblacionPlantasRegistrado');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al procesar la solicitud.');
        }
    }
    public function actualizarDetalle($filePath)
    {
        if (!$this->evaluacionBrotesXPisoId) {
            return;
        }

        $spreadsheet = ExcelHelper::cargarHoja('public', $filePath, $this->fileNameReporteBroteXPiso);
        $data = $spreadsheet->rangeToArray('B10:M22', null, true, false, true);

        // Asegurar que hay datos antes de continuar
        if (!$data || !is_array($data)) {
            return;
        }

        // Resetear los índices del array
        $data = array_values($data);

        // Eliminar registros previos
        EvaluacionBrotesXPisoDetalle::where('brotes_x_piso_id', $this->evaluacionBrotesXPisoId)->delete();

        $detalles = [];

        for ($i = 0; $i < count($data) - 1; $i++) { // Evitar la última fila de promedios
            $fila = $data[$i];

            // Verificar que las columnas B y C no sean vacías o nulas
            if (empty($fila['B']) || empty($fila['C'])) {
                continue; // Saltar esta fila
            }

            $detalles[] = [
                'brotes_x_piso_id' => $this->evaluacionBrotesXPisoId,
                'numero_cama_muestreada' => $fila['B'],
                'longitud_cama' => $fila['C'],
                'brotes_aptos_2p_actual' => $fila['D'] ?? null,
                'brotes_aptos_2p_despues_n_dias' => $fila['F'] ?? null,
                'brotes_aptos_3p_actual' => $fila['H'] ?? null,
                'brotes_aptos_3p_despues_n_dias' => $fila['J'] ?? null,
                'brotes_aptos_2p_actual_calculado' => $fila['E'] ?? null,
                'brotes_aptos_2p_despues_n_dias_calculado' => $fila['G'] ?? null,
                'brotes_aptos_3p_actual_calculado' => $fila['I'] ?? null,
                'brotes_aptos_3p_despues_n_dias_calculado' => $fila['K'] ?? null,
                'total_actual_de_brotes_aptos_23_piso_calculado' => $fila['L'] ?? null,
                'total_de_brotes_aptos_23_pisos_despues_n_dias_calculado' => $fila['M'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar datos solo si hay registros válidos
        if (!empty($detalles)) {
            EvaluacionBrotesXPisoDetalle::insert($detalles);

            $filaPromedios = $data[12]; // La fila 22 en Excel está en el índice 12 (porque empieza en fila 10)

            $promedios = [
                'promedio_actual_brotes_2piso' => $filaPromedios['E'] ?? 0,
                'promedio_brotes_2piso_n_dias' => $filaPromedios['G'] ?? 0,
                'promedio_actual_brotes_3piso' => $filaPromedios['I'] ?? 0,
                'promedio_brotes_3piso_n_dias' => $filaPromedios['K'] ?? 0,
                'promedio_actual_total_brotes_2y3piso' => $filaPromedios['L'] ?? 0,
                'promedio_total_brotes_2y3piso_n_dias' => $filaPromedios['M'] ?? 0,
            ];

            // Actualizar la tabla de evaluación con los valores extraídos
            EvaluacionBrotesXPiso::where('id', $this->evaluacionBrotesXPisoId)->update($promedios);
        }
    }


    public function generarReporteDetalle($datos)
    {
        // Verificar si hay datos
        $totalRegistros = count($datos);
        if ($totalRegistros === 0) {
            return ['file' => null];
        }
        if ($totalRegistros > 12) {
            throw new Exception("El detalle supera los 12 registros, lo que afectará la generación del Excel. Contacte soporte.");
        }

        // Filtrar datos vacíos
        $datosValidados = array_filter($datos, fn($fila) => !empty($fila['numero_cama_muestreada']) && !empty($fila['longitud_cama']));


        // Función para validar números
        $validarNumero = fn($valor, $tipo = 'int') => is_numeric($valor) ? ($tipo === 'int' ? (int) $valor : (float) $valor) : null;

        // Transformar datos
        $datos = array_map(fn($fila) => [
            'numero_cama_muestreada' => isset($fila['numero_cama_muestreada']) ? $validarNumero($fila['numero_cama_muestreada']) : null,
            'longitud_cama' => isset($fila['longitud_cama']) ? $validarNumero($fila['longitud_cama'], 'float') : null,
            'brotes_aptos_2p_actual' => isset($fila['brotes_aptos_2p_actual']) ? $validarNumero($fila['brotes_aptos_2p_actual']) : null,
            'brotes_aptos_2p_despues_n_dias' => isset($fila['brotes_aptos_2p_despues_n_dias']) ? $validarNumero($fila['brotes_aptos_2p_despues_n_dias']) : null,
            'brotes_aptos_3p_actual' => isset($fila['brotes_aptos_3p_actual']) ? $validarNumero($fila['brotes_aptos_3p_actual']) : null,
            'brotes_aptos_3p_despues_n_dias' => isset($fila['brotes_aptos_3p_despues_n_dias']) ? $validarNumero($fila['brotes_aptos_3p_despues_n_dias']) : null,
        ], $datosValidados);
        

        // Cargar plantilla de Excel
        $spreadsheet = ExcelHelper::cargarPlantilla('cartilla_evaluacion_brotes.xlsx');
        $hoja = $spreadsheet->getSheetByName('FORMATO') ?? throw new Exception("Formato de documento no encontrado.");

        // Configurar valores generales
        $hoja->setTitle($this->fileNameReporteBroteXPiso);
        $hoja->setCellValue("D3", $this->fecha);
        $hoja->setCellValue("D4", $this->evaluadorSeleccionado['nombre']);
        $hoja->setCellValue("D5", $this->metros_cama);
        $hoja->setCellValue("A10", $this->campania->campo);

        // Llenar datos en la hoja
        foreach ($datos as $index => $dato) {
            $fila = 10 + $index; // Empieza en la fila 10
            $hoja->setCellValue("B{$fila}", $dato['numero_cama_muestreada']);
            $hoja->setCellValue("C{$fila}", $dato['longitud_cama']);
            $hoja->setCellValue("D{$fila}", $dato['brotes_aptos_2p_actual']);
            $hoja->setCellValue("F{$fila}", $dato['brotes_aptos_2p_despues_n_dias']);
            $hoja->setCellValue("H{$fila}", $dato['brotes_aptos_3p_actual']);
            $hoja->setCellValue("J{$fila}", $dato['brotes_aptos_3p_despues_n_dias']);
        }

        // Generar nombre y ruta del archivo
        $folderPath = 'evaluacion/brotes_x_piso/' . date('Y-m');
        $fileName = "evaluacion_brote_x_piso_" . str_replace('-', '', $this->fecha) . "_t" . str_replace('.', '', $this->campania->nombre_campania) . "_campo" . $this->campania->campo . ".xlsx";
        $filePath = "{$folderPath}/{$fileName}";

        // Guardar el archivo
        Storage::disk('public')->makeDirectory($folderPath);
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(true);
        $writer->save(Storage::disk('public')->path($filePath));

        return ['file' => $filePath];
    }


    public function agregarEvaluacion()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['evaluadorSeleccionado', 'evaluador', 'metros_cama', 'fecha', 'evaluacionBrotesXPisoId', 'evaluacionBrotesXPiso']);
        $this->listaBroteXPlantaDetalle = [];
        $this->evaluadores = [];
        $this->dispatch('cargarDataBrotesXPiso', []);
    }
    public function quitarEvaluador()
    {
        $this->reset(['evaluadorSeleccionado']);
    }
    public function updatedEvaluador()
    {
        $this->evaluadores = Empleado::whereRaw(
            "CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?",
            ["%{$this->evaluador}%"]
        )
            ->limit(5)
            ->get()
            ->map(function ($evaluador) {
                return [
                    'id' => $evaluador->id,
                    'nombres' => $evaluador->nombre_completo
                ];
            })
            ->toArray();
    }
    public function seleccionarEvaluador($id, $nombre)
    {
        $this->reset(['evaluador', 'evaluadores']);
        $this->evaluadorSeleccionado = [
            'id' => $id,
            'nombre' => $nombre,
        ];
    }

    #endregion

    #region RENDER
    public function render()
    {
        return view('livewire.evaluacion-brotes-x-piso-por-campania-component');
    }
    #endregion
}
