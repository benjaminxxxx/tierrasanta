<?php

namespace App\Livewire\Campo;

use App\Models\Labores;
use App\Models\ManoObra;
use App\Services\Campo\Labor\ImportarLaborProceso;
use App\Services\Campo\Labor\LaborServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/**
 * Lista de labores (filtros, importación, eliminar y restaurar). El registro y la edición están en
 * LaborFormComponent: esta lista le pide abrirse con crearLabor / editarLabor y se refresca con laborGuardada.
 */
class LaboresComponent extends Component
{
    use WithPagination;
    use WithoutUrlPagination;
    use LivewireAlert;
    use WithFileUploads;
    public $search = '';
    public $manoObras;
    public $manoObraFiltro;
    public $fileLabores;
    public $tipoFiltro = '';
    public $verEliminados = false;
    public $afectoBonoFiltro = '';
    public $metodoBonoFiltro = '';
    public bool $verDisponibles = false;
    protected $listeners = ['eliminarLabor'];
    public function mount()
    {
        $this->manoObras = ManoObra::all();
        // ?mano_obra=sin → labores sin mano de obra (enlace de tareas pendientes)
        $manoObra = request()->query('mano_obra');
        if (is_string($manoObra) && $manoObra !== '') {
            $this->manoObraFiltro = $manoObra;
        }
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
    /** El formulario guardó: render() vuelve a leer la página actual con los cambios. */
    #[On('laborGuardada')]
    public function refrescar(): void
    {
    }
    public function confirmarEliminarLabor($id)
    {
        $labor = Labores::find($id);
        $usos = $labor ? LaborServicio::usos((int) $labor->codigo) : [];
        $mensaje = $usos
            ? "La labor {$labor->codigo} tiene registros (" . collect($usos)->map(fn($n, $o) => number_format($n) . " en {$o}")->implode(', ')
                . '). No se puede eliminar: se desactivará (ya no se podrá registrar, pero los reportes antiguos la siguen mostrando). ¿Continuar?'
            : '¿Eliminar la labor? No tiene registros: se elimina y su código queda libre.';
        $this->confirm($mensaje, [
            'onConfirmed' => 'eliminarLabor',
            'data' => [
                'laborId' => $id,
            ],
        ]);
    }
    public function eliminarLabor($data)
    {
        try {
            $resultado = LaborServicio::eliminar($data['laborId']);
            $this->alert('success', $resultado === 'desactivada' ? 'Labor desactivada: sus registros y reportes se conservan.' : 'Labor eliminada.');
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
    public function updatedTipoFiltro()
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
        $filtros = [
            'buscar' => $this->search,
            'mano_obra' => $this->manoObraFiltro,
            'afecto_bono' => $this->afectoBonoFiltro,
            'metodo_bono' => $this->metodoBonoFiltro,
            'tipo' => $this->tipoFiltro,
        ];

        $labores = LaborServicio::leer($filtros, 10, $this->verEliminados);

        return view('livewire.campo.labores-component', [
            'labores' => $labores,
            // Filas que usan cada código: con registros, "eliminar" solo desactiva
            'usos' => LaborServicio::usosPorCodigo(collect($labores->items())->pluck('codigo')->filter()->all()),
            // Cuántas labores anteriores tuvo cada código (se reutilizó)
            'historias' => \App\Models\LaborVigencia::whereIn('codigo', collect($labores->items())->pluck('codigo')->filter())
                ->selectRaw('codigo, COUNT(*) n')->groupBy('codigo')->pluck('n', 'codigo'),
            'disponibles' => $this->verDisponibles ? app(\App\Services\Campo\Labor\CampoLaborVigenciaConsulta::class)->disponibles() : [],
            'mesesReutilizar' => \App\Services\Campo\Labor\CampoLaborVigenciaConsulta::mesesParaReutilizar(),
            // La mano de obra es obligatoria, pero labores antiguas pueden seguir sin ella
            'sinManoObra' => Labores::where(fn($q) => $q->whereNull('codigo_mano_obra')->orWhere('codigo_mano_obra', ''))->count(),
        ]);
    }
}
