<?php

namespace App\Livewire;

use App\Models\Maquinaria;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class MaquinariaFormComponent extends Component
{
    use LivewireAlert;
    public $nombre;
    public $alias_blanco;
    public $mostrarFormulario = false;
    public $maquinaria_id;
    protected $listeners = ['EditarMaquinaria','RegistrarMaquinaria'];
    protected function rules()
    {
        return [
            'nombre'=>'required',
            'alias_blanco'=>'required'
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la maquinaria es obligatorio.',
        'alias_blanco.required' => 'El nombre alias es obligatorio para su uso en Kardex blanco.',
    ];
    public function RegistrarMaquinaria()
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function EditarMaquinaria($id)
    {
        $this->resetForm();
        
        $maquinaria = Maquinaria::find($id);
        if ($maquinaria) {
            $this->maquinaria_id = $maquinaria->id;
            $this->nombre = $maquinaria->nombre;
            $this->alias_blanco = $maquinaria->alias_blanco;
            $this->mostrarFormulario = true;
        }
    }
    public function store()
    {
        $this->validate();

        try {
            $data = [
                'nombre' => mb_strtoupper(trim($this->nombre)),
                'alias_blanco' => mb_strtoupper(trim($this->alias_blanco))
            ];

            if ($this->maquinaria_id) {
                $maquinaria = Maquinaria::find($this->maquinaria_id);
                if ($maquinaria) {
                    $maquinaria->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                Maquinaria::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar
            $this->resetForm();
            $this->dispatch('ActualizarMaquinarias');
            $this->closeForm();
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function closeForm()
    {
        $this->mostrarFormulario = false;
    }
    public function resetForm(){
        $this->reset(['nombre','alias_blanco','maquinaria_id']);
    }
    public function render()
    {
        return view('livewire.maquinaria-form-component');
    }
}
