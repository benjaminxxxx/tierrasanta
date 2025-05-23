<?php

namespace App\Livewire;

use App\Models\Campo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CamposComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $campoNombre;
    public $campoNombreEditar;
    public $campoPadre;
    public $area;
    public $alias;
    public $mostrarFormulario = false;
    public $estaEditando = false;
    public $filtroCampo;
    protected $listeners = ['campaniaInsertada' => '$refresh'];
    public function render()
    {
        $query = Campo::orderBy('orden');

        if (!empty($this->filtroCampo)) {
            $query->where('nombre', $this->filtroCampo );
        }

        $campos = $query->paginate(20);

        return view('livewire.campos-component', [
            'campos' => $campos,
        ]);
    }
    public function editarCampo($campoNombre)
    {
        $this->resetErrorBag();
        $this->estaEditando = false;
        $campo = Campo::where('nombre', $campoNombre)->first();
        if ($campo) {
            $this->campoNombreEditar = $campoNombre;
            $this->estaEditando = true;
            $this->campoNombre = $campoNombre;
            $this->campoPadre = $campo->campo_parent_nombre;
            $this->area = $campo->area;
            $this->alias = $campo->alias;
            $this->mostrarFormulario = true;
        } else {
            $this->estaEditando = false;
            $this->campoNombre = null;
            $this->campoPadre = null;
            $this->area = null;
            $this->alias = null;
            $this->mostrarFormulario = false;
        }

    }
    public function registrarCampo()
    {
        $this->resetErrorBag();
        $this->estaEditando = false;
        $this->campoNombre = null;
        $this->campoPadre = null;
        $this->area = null;
        $this->alias = null;
        $this->mostrarFormulario = true;
        $this->campoNombreEditar = null;
    }
    public function storeCampos()
    {
        $this->validate([
            'campoNombre' => 'required|unique:campos,nombre,' . $this->campoNombreEditar . ',nombre',
            'area' => 'required',
        ], [
            'campoNombre.required' => 'El nombre del campo es obligatorio.',
            'campoNombre.unique' => 'Este nombre de campo ya existe.',
            'area.required' => 'El área es obligatoria.',
        ]);

        $data = [
            'nombre' => $this->campoNombre,
            'area' => $this->area,
            'campo_parent_nombre' => $this->campoPadre,
            'alias' => $this->alias,
        ];

        if ($this->estaEditando) {
            $campo = Campo::find($this->campoNombreEditar);

            if ($campo) {
                // Si el nombre cambia (clave primaria), hay que reemplazar el registro
                if ($this->campoNombre !== $this->campoNombreEditar) {
                    // Eliminar el antiguo y crear uno nuevo (simula un "rename")
                    $campo->delete();
                    Campo::create($data);
                } else {
                    $campo->update($data);
                }
            }
        } else {
            Campo::create($data);
        }

        $this->reset(['campoNombre', 'area', 'campoPadre', 'alias', 'campoNombreEditar', 'estaEditando']);
        $this->alert('success', 'Campo registrado correctamente.');
        $this->mostrarFormulario = false;
    }


}
