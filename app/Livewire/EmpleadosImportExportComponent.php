<?php

namespace App\Livewire;

use App\Models\AsignacionFamiliar;
use App\Models\PlanCargo;
use App\Models\PlanDescuentoSP;
use App\Models\PlanGrupo;
use DateTime;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\PlanEmpleado;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Exports\EmpleadosExport;
use Maatwebsite\Excel\Facades\Excel;

class EmpleadosImportExportComponent extends Component
{
    use WithFileUploads;
    use LivewireAlert;
    public $file;
    public $fileExport;
    public $empleadosNoImportados = [];
    public $empleadosNoImportadosQuery;
    public $isFormOpen = false;
    public $empleadoCode;
    protected $listeners = ['eliminacionEmlpeadoConfirmado'];

    public function updatedFile()
    {
        if ($this->file) {

            try {
                // Validar el archivo
                $this->validate([
                    'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
                ]);

                // Importar el archivo
                // Leer el archivo usando PHPSpreadsheet
                $spreadsheet = IOFactory::load($this->file->getRealPath());

                $this->processEmpleadosSheet($spreadsheet);
                $this->processAsignacionFamiliarSheet($spreadsheet);

                // Mostrar una alerta de éxito
                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('EmpleadoRegistrado');
            } catch (Exception $e) {
                // Manejar excepciones
                $this->alert('error', 'Hubo un error al importar los datos: ' . $e->getMessage());
            }
        }
    }
    public function export()
    {
        return Excel::download(new EmpleadosExport, date('Y-m-d') . '_Empleados.xlsx');
    }
    protected function processEmpleadosSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('Empleados');
        if (!$sheet) {
            throw new Exception("La Hoja Empleados dentro del archivo No existe, usar la plantilla correcta");
        }
        $rows = $sheet->toArray();
        $grupos = Grupo::all()->pluck('codigo')->toArray();

        // Procesar los datos a partir de la segunda fila (índice 1)
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }
            $orden = $row[0] ?? 999;
            $nombres = $row[3] ?? 'SIN NOMBRE';
            $apellido_paterno = $row[1] ?? null;
            $apellido_materno = $row[2] ?? null;
            $documento = $row[4] ?? null;
            $fecha_ingreso = $row[5] ?? null;
            $fecha_nacimiento = $row[6] ?? null;
            $cargo_nombre = $row[7] ?? null;
            $descuento_sp_codigo = $row[8] ?? null;
            $genero = strtoupper($row[9] ?? null);
            $salario = $row[10] ?? null;
            $grupo_codigo = $row[11] ?? null;
            $compensacion_vacacional = $row[12] ?? null;
            $esta_jubilado = $row[13] ?? 0;
            $estado = $row[14] ?? 'inactivo';
          

            if($fecha_ingreso!=null){
                $fecha_ingreso = $this->validarFecha($fecha_ingreso);
            }
            if($fecha_nacimiento!=null){
                $fecha_nacimiento = $this->validarFecha($fecha_nacimiento);
            }
            

            if ($documento) {

                $grupo_codigo = in_array($grupo_codigo, $grupos) ? $grupo_codigo : null;
                $compensacion_vacacional = $this->validarCompensacionVacacional($compensacion_vacacional);
                $esta_jubilado = $this->validarEstadoJubilado($esta_jubilado);

                $cargo_codigo = null;
                $descuento_sp_id = null;

                if ($salario !== '-' && $salario !== '') {
                    // Eliminar comas (separador de miles) y convertir a número
                    $salario = str_replace(',', '', $salario);

                    // Verificar si es un número válido, de lo contrario asignar null
                    $salario = is_numeric($salario) ? $salario : null;
                } else {
                    $salario = null;
                }

                if ($cargo_nombre && $cargo_nombre !== '-') {
                    $cargo_nombre = strtoupper($cargo_nombre);

                    $cargo = PlanCargo::whereRaw('LOWER(nombre) = ?', [strtolower($cargo_nombre)])->first();

                    if (!$cargo) {
                        // Generar un código único para el nuevo cargo
                        $base_codigo = substr($cargo_nombre, 0, 3);
                        $codigo = $base_codigo;
                        $counter = 1;

                        while (PlanCargo::where('codigo', $codigo)->exists()) {
                            $codigo = $base_codigo . $counter;
                            $counter++;
                        }

                        // Crear un nuevo cargo
                        $cargo = PlanCargo::create([

                            'codigo' => mb_strtoupper($codigo),
                            'nombre' => $cargo_nombre
                        ]);
                    }

                    $cargo_codigo = $cargo->codigo;
                }

                if ($descuento_sp_codigo && $descuento_sp_codigo !== '-') {
                    $descuento_sp = PlanDescuentoSP::where('codigo', $descuento_sp_codigo)->first();
                    $descuento_sp_id = $descuento_sp ? $descuento_sp->codigo : null;
                }

                if ($genero !== 'M' && $genero !== 'F') {
                    $genero = null;
                }

                $data = [
                    'nombres' => $nombres,
                    'apellido_paterno' => $apellido_paterno,
                    'apellido_materno' => $apellido_materno,
                    'documento' => $documento,
                    'cargo_id' => $cargo_codigo,
                    'descuento_sp_id' => $descuento_sp_id,
                    'genero' => $genero,
                    'fecha_ingreso' => ($fecha_ingreso !== '-' && $fecha_ingreso !== '') ? $fecha_ingreso : null,
                    'fecha_nacimiento' => ($fecha_nacimiento !== '-' && $fecha_nacimiento !== '') ? $fecha_nacimiento : null,
                    'salario' => ($salario !== '-' && $fecha_nacimiento !== '') ? $salario : null,
                    'grupo_codigo'=>$grupo_codigo,
                    'compensacion_vacacional'=>$compensacion_vacacional,
                    'esta_jubilado'=>$esta_jubilado,
                    'status'=>$estado,
                    'orden'=>$orden,
                ];

                $empleado = PlanEmpleado::where('documento', $documento)->first();

                if ($empleado) {

                    
                    // Actualizar el registro existente
                    $empleado->update($data);
                } else {
                    // Crear un nuevo registro
                    $data['code'] = Str::random(15);
                    PlanEmpleado::create($data);
                }
                $this->empleadosNoImportados[] = $documento;
            }
        }

        $this->empleadosNoImportadosQuery = PlanEmpleado::whereNotIn('documento',$this->empleadosNoImportados)->get();
        if($this->empleadosNoImportadosQuery->count()>0){
            $this->isFormOpen = true;
        }
    }
    public function cerrarForm(){
        $this->isFormOpen = false;
        $this->empleadosNoImportadosQuery = null;
        $this->empleadosNoImportados = [];
    }
    public function confirmarEliminacion($code)
    {
        $this->empleadoCode = $code;

        $this->alert('question', '¿Está seguro que desea eliminar al Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionEmlpeadoConfirmado',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function eliminacionEmlpeadoConfirmado()
    {
        if ($this->empleadoCode) {
            $empleado = PlanEmpleado::where('code', $this->empleadoCode);
            if ($empleado) {
                $empleado->delete();
                $this->empleadoCode = null;
                $this->cerrarForm();
            }
        }
    }
    private function validarEstadoJubilado($value)
    {
        // Convertir a mayúsculas
        $value = mb_strtoupper(trim($value));

        // Verificar si el valor es "SI"
        return ($value === 'SI') ? 1 : 0;
    }
    private function validarCompensacionVacacional($value)
    {
        // Verificar si el valor es numérico y cumple con el formato 10,2
        if (is_numeric($value) && preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            // Asegurarse de que el valor tenga hasta 2 decimales
            return number_format((float) $value, 2, '.', '');
        }

        // Si no es válido, devolver 0
        return '0.00';
    }
    protected function processAsignacionFamiliarSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('AsignacionFamiliar');
        if (!$sheet) {
            throw new Exception("La Hoja AsignacionFamiliar dentro del archivo No existe, usar la plantilla correcta");
        }

        $rows = $sheet->toArray();

        // Procesar los datos a partir de la segunda fila (índice 1)
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }

            // Aquí debes ajustar el índice según las columnas en la hoja AsignacionFamiliar
            $empleado_documento = $row[1] ?? null;
            $familiar_nombre = $row[3] ?? 'SIN NOMBRE';
            $familiar_documento = $row[4] ?? null;
            $familiar_fecha_nacimiento = $row[5] ?? null;
            $familiar_esta_estudiando = $row[6] == 'SI' ? 1 : 0;

            $familiar_fecha_nacimiento = $this->validarFecha($familiar_fecha_nacimiento);

            if ($empleado_documento && $familiar_documento && $familiar_fecha_nacimiento) {

                $empleado = PlanEmpleado::where('documento', $empleado_documento)->first();

                if ($empleado) {

                    

                    AsignacionFamiliar::updateOrCreate(
                        ['documento' => $familiar_documento],
                        [
                            'empleado_id' => $empleado->id,
                            'nombres' => $familiar_nombre,
                            'documento' => $familiar_documento,
                            'fecha_nacimiento' => $familiar_fecha_nacimiento,
                            'esta_estudiando' => $familiar_esta_estudiando
                        ]
                    );
                }
            }
        }


    }
    public function validarFecha($fecha)
    {
        // Verifica si la fecha no es '-' y no está vacía
        if ($fecha !== '-' && $fecha !== '') {
            // Intenta convertir la fecha
            try {
                $fechaObj = new DateTime($fecha);
                return $fechaObj->format('Y-m-d'); // Retorna la fecha en formato correcto
            } catch (Exception $e) {
                // Si no es una fecha válida, retorna null
                return null;
            }
        } else {
            return null;
        }
    }
    public function render()
    {
        return view('livewire.empleados-import-export-component');
    }
}
