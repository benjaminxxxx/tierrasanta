<?php

namespace App\Livewire;

use App\Models\Labores;
use App\Models\ManoObra;
use App\Services\Labor\ImportarLaborProceso;
use App\Services\Labor\LaborServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class LaboresComponent extends Component
{
    use WithPagination;
    use WithoutUrlPagination;
    use LivewireAlert;
    use WithFileUploads;
    public $laborId;
    public $search = '';
    public $mostrarFormularioLabor = false;
    public $codigo;
    public $nombre_labor;
    public $estandar_produccion;
    public $unidades;
    public $codigo_mano_obra;
    public $manoObras;
    public $manoObraFiltro;
    public $fileLabores;
    public $verEliminados = false;
    // App/Http/Livewire/MiComponente.php
    public array $tramos = [
        ['hasta' => '', 'monto' => '']
    ];
    protected $listeners = ['eliminarLabor'];
    public function mount()
    {
        $this->manoObras = ManoObra::all();
    }
    public function updatedFileLabores($file)
    {
        try {
            $registros = app(ImportarLaborProceso::class)->ejecutar($file);
            $this->fileLabores = null;
            $this->alert('success', "Labores importadas correctamente. {$registros} registros procesados.");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage(), [
                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
        }
    }
    public function crearNuevaLabor()
    {
        $this->resetForm();
        $this->mostrarFormularioLabor = true;
    }
    public function editarLabor($laborId)
    {
        $this->resetForm();
        $this->laborId = $laborId;
        $labor = Labores::find($this->laborId);
        if ($labor) {
            $this->codigo = $labor->codigo;
            $this->nombre_labor = $labor->nombre_labor;
            $this->estandar_produccion = $labor->estandar_produccion;
            $this->unidades = $labor->unidades;
            $this->codigo_mano_obra = $labor->codigo_mano_obra;
            $this->tramos = $labor->tramos_bonificacion != null ? json_decode($labor->tramos_bonificacion, true) : [['hasta' => '', 'monto' => '']];
            $this->mostrarFormularioLabor = true;
        } else {
            $this->alert('error', 'Labor no encontrada.');
        }
    }
    public function guardarLabor()
    {
        try {

            $data = [
                'codigo' => $this->codigo,
                'nombre_labor' => $this->nombre_labor,
                'estandar_produccion' => $this->estandar_produccion,
                'unidades' => $this->unidades,
                'tramos_bonificacion' => empty($this->tramos) ? null : json_encode($this->tramos),
                'codigo_mano_obra' => $this->codigo_mano_obra,
            ];
            LaborServicio::guardar($data, $this->laborId);

            $this->resetForm();
            $this->mostrarFormularioLabor = false;
            $this->alert('success', 'Labor guardada correctamente.');

        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['laborId', 'codigo', 'nombre_labor', 'codigo_mano_obra', 'estandar_produccion', 'unidades', 'tramos']);

    }
    public function confirmarEliminarLabor($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?.', [
            'onConfirmed' => 'eliminarLabor',
            'data' => [
                'laborId' => $id,
            ],
        ]);
    }
    public function eliminarLabor($data)
    {
        try {
            LaborServicio::eliminar($data['laborId']);
            $this->alert('success', 'Registro eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatedManoObraFiltro()
    {
        $this->resetPage();
    }
    public function updatedVerEliminados()
    {
        $this->resetPage();
    }
    public function restaurarLabor($id)
    {
        try {
            $labor = Labores::withTrashed()->findOrFail($id);
            $labor->restore();
            $this->alert('success', 'Labor restaurada correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Error al restaurar la labor: ' . $th->getMessage());
        }
    }
    public function render()
    {
        // Preparamos los filtros en un array
        $filtros = [
            'buscar' => $this->search,
            'mano_obra' => $this->manoObraFiltro,
        ];

        // Llamamos al servicio (le pasamos 10 para que pagine)
        $labores = LaborServicio::leer($filtros, 10, $this->verEliminados);

        return view('livewire.labores-component', [
            'labores' => $labores
        ]);
    }
}
