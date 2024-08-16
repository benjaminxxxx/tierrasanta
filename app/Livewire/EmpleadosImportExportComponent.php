<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Empleado;
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
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Procesar los datos a partir de la segunda fila (índice 1)
                foreach ($rows as $index => $row) {
                    if ($index === 0) {
                        // Omitir la primera fila (encabezados)
                        continue;
                    }

                    $nombres = $row[3] ?? 'SIN NOMBRE';
                    $apellido_paterno = $row[1] ?? null;
                    $apellido_materno = $row[2] ?? null;
                    $documento = $row[4] ?? null;

                    if ($documento) {
                        // Buscar si el documento ya existe
                        $empleado = Empleado::where('documento', $documento)->first();

                        if ($empleado) {
                            // Actualizar el registro existente
                            $empleado->update([
                                'nombres' => $nombres,
                                'apellido_paterno' => $apellido_paterno,
                                'apellido_materno' => $apellido_materno,
                            ]);
                        } else {
                            // Crear un nuevo registro
                            Empleado::create([
                                'code' => Str::random(15),
                                'nombres' => $nombres,
                                'apellido_paterno' => $apellido_paterno,
                                'apellido_materno' => $apellido_materno,
                                'documento' => $documento,
                            ]);
                        }
                    }
                }
                // Mostrar una alerta de éxito
                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('EmpleadoRegistrado');
            } catch (\Exception $e) {
                // Manejar excepciones
                $this->alert('error', 'Hubo un error al importar los datos: ' . $e->getMessage());
            }
        }
    }
    public function export(){
        return Excel::download(new EmpleadosExport, date('Y-m-d') . '_Empleados.xlsx');
    }
    public function render()
    {
        return view('livewire.empleados-import-export-component');
    }
}
