<?php

namespace App\Livewire\GestionPlanilla;

use App\Services\PlanillaServicio;
use App\Services\RecursosHumanos\Data\DataEmpleadoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use App\Support\DataPlanillaHelper;
use App\Support\ExcelHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportarPlanillaComponent extends Component
{
    use WithFileUploads;
    use LivewireAlert;
    public $activeTab = 'upload';
    public $archivo; // Para el input file de Livewire
    public $archivoCargado = false;
    public $nombreArchivo = null;
    public $data = [];
    public $dataOriginal = [];
    public function mount()
    {
        $this->obtenerDataOriginal();
    }
    public function obtenerDataOriginal()
    {
        $this->dataOriginal = app(DataEmpleadoServicio::class)->obtenerDataEmpleados();
    }
    public function updatedArchivo()
    {
        $this->validate([
            'archivo' => 'mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        $this->nombreArchivo = $this->archivo->getClientOriginalName();
        $this->archivoCargado = true;

        try {
            $hojas = [
                'EMPLEADOS' => 'tblEmpleados',
                'CONTRATACIONES' => 'tblContrataciones',
                'SUELDOS' => 'btlSueldos',
                'HIJOS' => 'btlHijos',
            ];
            $data = ExcelHelper::cargarData($this->archivo, $hojas);
            $this->data = DataPlanillaHelper::detectarCambios($this->dataOriginal, $data);
            
            $this->activeTab = 'preview';
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function cancelar()
    {
        $this->reset(['archivo', 'archivoCargado', 'data', 'nombreArchivo']);
        $this->activeTab = 'upload'; // Regresar al primer tab
        $this->obtenerDataOriginal();
    }

    public function procesarImportacion()
    {
        try {
            // Aquí llamarás a un servicio que haga los DB::transaction()
            app(EmpleadoServicio::class)->guardarDataDesdeExcel($this->data);
            $this->alert('success', '¡Datos importados con éxito!');
            $this->cancelar(); // Limpiar todo al finalizar
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function descargarPlanilla(){
        try {
            return app(PlanillaServicio::class)->descargarPlanillaActualizada();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.importar-planilla-component');
    }
}
