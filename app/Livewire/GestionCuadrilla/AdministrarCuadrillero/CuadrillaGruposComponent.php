<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\CuaGrupo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CuadrillaGruposComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    use WithoutUrlPagination;
    public $verEliminados;
    protected $listeners = ['confirmarEliminar','grupoRegistrado'=>'$refresh'];

    public function confirmarEliminarGrupo($codigo)
    {
        if (!$codigo)
            return;

        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminar',
            'data' => [
                'codigo' => $codigo,
            ],
        ]);
    }
    public function restaurar($codigo){
        CuaGrupo::where('codigo', $codigo)->update(['estado'=>true]);
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function updatedVerEliminados(){
        $this->resetPage();
    }
    public function confirmarEliminar($data)
    {
        try {
            $codigo = $data['codigo'];
            CuaGrupo::where('codigo', $codigo)->update(['estado'=>false]);
            $this->alert('success', 'Registro Eliminado Correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $grupos = CuaGrupo::with('fechasCuadrilleros')->where('estado',!$this->verEliminados)->orderByDesc('created_at')->paginate(10);
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupos-component',[
            'grupos'=>$grupos
        ]);
    }
}
