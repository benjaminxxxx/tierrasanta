<?php

namespace App\Livewire\GestionSiembra;

use App\Models\Siembra;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class SiembraListComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $filtroCampo = '';
    public $filtroAnio = '';
    public $aniosDisponibles = [];
    protected $listeners = ['siembraGuardada'=>'$refresh','eliminarSiembra'];
    public function mount()
    {
        // Obtener los años únicos de las fechas de siembra
        $this->aniosDisponibles = Siembra::selectRaw('YEAR(fecha_siembra) as anio')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->toArray();
    }
  
    public function updatingFiltroCampo()
    {
        $this->resetPage();
    }

    public function updatingFiltroAnio()
    {
        $this->resetPage();
    }

    public function preguntarEliminarSiembra($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'eliminarSiembra',
            'data' => [
                'siembraId' => $id,
            ],
        ]);
    }
    public function eliminarSiembra($data)
    {
        try {
            $siembraId = $data['siembraId'];    
            $siembra = Siembra::findOrFail($siembraId);
            $siembra->delete();
    
            $this->alert('success', 'La siembra se eliminó correctamente.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('log', 'Registro no encontrado: ' . $e->getMessage());
            $this->alert('error', 'La siembra no existe o ya fue eliminado.');
        } catch (\Exception $e) {
            $this->dispatch('log', 'Error al eliminar: ' . $e->getMessage());
            $this->alert('error', 'Ocurrió un error interno al intentar eliminar la siembra.');
        }
    }
    public function render()
    {
        $query = Siembra::query();

        // Filtrar por campo si se selecciona uno
        if (!empty($this->filtroCampo)) {
            $query->where('campo_nombre', $this->filtroCampo);
        }

        // Filtrar por año si se selecciona uno
        if (!empty($this->filtroAnio)) {
            $query->whereYear('fecha_siembra', $this->filtroAnio);
        }

        $listaSiembra = $query->orderBy('fecha_siembra','desc')
            ->orderBy('campo_nombre')
            ->paginate(20);

        return view('livewire.gestion-siembra.siembra-list-component', [
            'siembraLista' => $listaSiembra
        ]);
    }
}
