<?php

namespace App\Livewire;

use App\Models\Labores;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class LaboresComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $verEliminados = false;
    public $laborId;
    public $search = '';
    public $nuevaLabor;
    public $conBono;
    protected $listeners = ['laborRegistrada'=>'$refresh','confirmarEliminar','valoracionTrabajada'=>'$refresh'];
    public function editarLabor($laborId){
        $this->laborId = $laborId;
        $labor = Labores::find($this->laborId);
        if($labor){
            $this->nuevaLabor = $labor->nombre_labor;
        }
    }
    public function agregarLabor(){
        try {
            $data = ['nombre_labor'=>$this->nuevaLabor];
            if($this->laborId){
                Labores::find($this->laborId)->update($data);
                $this->alert('success', 'Registro actualizado correctamente.'); 
            }else{
                Labores::create($data);
                $this->alert('success', 'Registro agregado correctamente.'); 
            }
            $this->reset(['laborId','nuevaLabor']);
            
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar la Labor.'); 
        }
    }
    public function confirmarEliminarLabor($id)
    {

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
                'laborId' => $id,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $laborId = $data['laborId'];
        Labores::find($laborId)->update(['estado'=>false]);
        $this->alert('success', 'Registro eliminado correctamente.');
    }
    public function restaurar($laborId){
        Labores::find($laborId)->update(['estado'=>true]);
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function updatedVerEliminados(){
        $this->resetPage();
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingConBono()
    {
        $this->resetPage();
    }
    public function toggleBono($laborId,$estado){
        Labores::find($laborId)->update(['bono'=>$estado==1]);
        $this->alert('success', 'Registro actualizado correctamente.');
    }
    public function render()
    {
        $query = Labores::query();
        if($this->conBono){
            $query->where('bono',$this->conBono=='si'?'1':'0');
        }
        $query->where('estado',!$this->verEliminados);
        $query->where('nombre_labor','like', '%' . $this->search . '%');
        $labores = $query->paginate(10);

        return view('livewire.labores-component',[
            'labores'=>$labores
        ]);
    }
}
