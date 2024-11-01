<?php

namespace App\Livewire;

use App\Models\CuaGrupo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaGruposComponent extends Component
{
    use LivewireAlert;
    public $grupos;
    public $verEliminados;
    protected $listeners = ['confirmarEliminar','grupoRegistrado'=>'$refresh'];

    public function render()
    {
        $this->grupos = CuaGrupo::where('estado',!$this->verEliminados)->get();
        return view('livewire.cuadrilla-grupos-component');
    }
    public function confirmarEliminarGrupo($codigo)
    {
        if (!$codigo)
            return;

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'codigo' => $codigo,
            ],
        ]);
    }
    public function restaurar($codigo){
        CuaGrupo::where('codigo', $codigo)->update(['estado'=>true]);
        $this->alert('success', 'Registro restaurado correctamente.');
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
}
