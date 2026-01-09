<?php

namespace App\Livewire\GestionProveedor;

use App\Models\TiendaComercial;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProveedoresFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioProveedores = false;
    public $proveedorId;
    public $nombre;
    public $ruc;
    public $contacto;
    protected $listeners = ['editarProveedor', 'crearProveedor'];
    protected function rules()
    {
        return [
            'nombre' => 'required|string',
            'ruc' => [
                'nullable',
                'numeric',
                'digits:11',
                Rule::unique('tienda_comercials', 'ruc')->ignore($this->proveedorId),
            ]
        ];
    }

    protected $messages = [
        'nombre.required' => 'El Nombre es obligatorio.',
        'ruc.unique' => 'El Ruc ya está en uso.',
        'ruc.digits' => 'El Ruc debe tener exactamente 11 dígitos.',
        'ruc.numeric' => 'El Ruc debe ser numérico.'
    ];
    public function crearProveedor()
    {
        $this->mostrarFormularioProveedores = true;
        $this->proveedorId = null;
        $this->nombre = null;
        $this->ruc = null;
        $this->contacto = null;
    }
    public function editarProveedor($id)
    {
        $proveedor = TiendaComercial::find($id);
        if ($proveedor) {
            $this->proveedorId = $proveedor->id;
            $this->nombre = $proveedor->nombre;
            $this->ruc = $proveedor->ruc;
            $this->contacto = $proveedor->contacto;
            $this->mostrarFormularioProveedores = true;
        }
    }
    public function guardarProveedores()
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
            $this->mostrarFormularioProveedores = false;
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-proveedor.proveedores-form-component');
    }
}
