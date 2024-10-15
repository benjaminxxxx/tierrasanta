<?php

namespace App\Livewire;

use App\Models\TiendaComercial;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProveedoresFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $proveedorId;
    public $nombre;
    public $ruc;
    public $contacto;
    protected $listeners = ['EditarProveedor','CrearProveedor'];
    protected function rules()
    {
        return [
            'nombre' => 'required|string',
            'ruc' => [
                'nullable',
                'string',
                Rule::unique('tienda_comercials', 'ruc')->ignore($this->proveedorId),
            ]
        ];
    }

    protected $messages = [
        'nombre.required' => 'El Nombre es obligatorio.',
        'ruc.unique' => 'El Ruc ya está en uso.'
    ];
    public function CrearProveedor()
    {
        $this->mostrarFormulario = true;
    }
    public function EditarProveedor($id)
    {
        $proveedor = TiendaComercial::find($id);
        if ($proveedor) {
            $this->proveedorId = $proveedor->id;
            $this->nombre = $proveedor->nombre;
            $this->ruc = $proveedor->ruc;
            $this->contacto = $proveedor->contacto;
            $this->mostrarFormulario = true;
        }
    }
    public function store()
    {
        $this->validate();

        try {
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'ruc' => mb_strtoupper($this->ruc),
                'contacto' => mb_strtoupper($this->contacto)
            ];

            if ($this->proveedorId) {
                $proveedor = TiendaComercial::find($this->proveedorId);
                if ($proveedor) {
                    $proveedor->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                TiendaComercial::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar
            $this->reset([
                'nombre',
                'ruc',
                'contacto',
                'proveedorId'
            ]);
            $this->dispatch('ActualizarProveedores');
            $this->closeForm();
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function closeForm()
    {
        $this->mostrarFormulario = false;
    }
    public function render()
    {
        return view('livewire.proveedores-form-component');
    }
}
