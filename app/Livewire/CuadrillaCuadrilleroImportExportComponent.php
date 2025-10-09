<?php

namespace App\Livewire;
use App\Models\Cuadrillero;
use App\Models\GruposCuadrilla;
use DB;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Exports\CuadrillerosExport;
use Maatwebsite\Excel\Facades\Excel;

class CuadrillaCuadrilleroImportExportComponent extends Component
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

                $this->procesarCuadrilleros($spreadsheet);

                // Mostrar una alerta de éxito
                $this->alert('success', 'Los datos se importaron correctamente.');
                $this->dispatch('CuadrillerosRegistrados');
            } catch (Exception $e) {
                // Manejar excepciones
                $this->alert('error', 'Hubo un error al importar los datos: ' . $e->getMessage());
            }
        }
    }
    protected function procesarCuadrilleros($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('Cuadrilleros');
        if (!$sheet) {
            throw new Exception("La Hoja Cuadrilleros dentro del archivo No existe, usar la plantilla correcta");
        }

        $rows = $sheet->toArray();
        $grupos = CuaGrupo::all()->pluck('codigo')->toArray();

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }

            $nombres = $row[1] ?? 'SIN NOMBRE';
            $codigo_grupo = $row[2] ?? null;
            $documento = $row[3] ?? null;
            $identificador = $row[4] ?? null;

            $codigo_grupo = in_array($codigo_grupo, $grupos) ? $codigo_grupo : null;

            if ($codigo_grupo) {
                $data = [
                    'nombre_completo' => $nombres,
                    'codigo_grupo' => $codigo_grupo,
                    'dni' => $documento,
                ];

                $cuadrillero = null;

                if ($identificador) {
                    // Si hay un identificador, se busca por dni o identificador
                    $codigoNumerico = (int) substr($identificador, 2); // Extrae el número después de 'CU'

                    // Buscar al trabajador por código o ID
                    $data['id']=$codigoNumerico;
                    $data['codigo']=$identificador;
                    $cuadrillero = Cuadrillero::where('codigo', $identificador)
                        ->orWhere('id', $codigoNumerico)
                        ->first();

                } elseif ($documento) {
                    // Si no hay identificador pero hay documento, se busca solo por documento
                    $cuadrillero = Cuadrillero::where('dni', $documento)->first();
                } else {
                    $cuadrillero = Cuadrillero::where('nombre_completo', $nombres)
                        ->first();
                }


                if ($cuadrillero) {
                    // Actualizar el registro existente
                    $cuadrillero->update($data);
                } else {
                    if($identificador){
                        DB::table('cuad_cuadrilleros')->insert($data); //esto permite insercion de id personalizado
                    }else{
                        Cuadrillero::create($data);
                    }
                    
                }
            }
        }
    }
    public function export()
    {
        return Excel::download(new CuadrillerosExport, date('Y-m-d') . '_Cuadrilleros.xlsx');
    }
    public function render()
    {
        return view('livewire.cuadrilla-cuadrillero-import-export-component');
    }
}
