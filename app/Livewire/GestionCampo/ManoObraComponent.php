<?php

namespace App\Livewire\GestionCampo;
use App\Models\ManoObra;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ManoObraComponent extends Component
{
    use LivewireAlert;
    public $manoObras;
    public $manoObraCodigo; //usado para editar y cambio el codigo de la mano de obra
    public $codigo;
    public $descripcion;
    public $mostrarFormularioManoObra = false;
    protected $listeners = [
        'confirmarEliminarManoObra',
    ];
    public function mount()
    {
        $this->obtenerManoObras();
    }
    public function obtenerManoObras()
    {
        $this->manoObras = ManoObra::all();
    }
    public function abrirFormManoObra($codigo = null)
    {
        try {
            $this->reset('manoObraCodigo', 'codigo', 'descripcion');
            $this->resetErrorBag();

            if ($codigo) {
                $manoObra = ManoObra::where('codigo', $codigo)->firstOrFail();

                $this->manoObraCodigo = $codigo;
                $this->codigo = $manoObra->codigo;
                $this->descripcion = $manoObra->descripcion;
            }
            $this->mostrarFormularioManoObra = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function guardarManoObra()
    {
        $this->validate([
            'codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mano_obras', 'codigo')->ignore($this->manoObraCodigo, 'codigo')
            ],
            'descripcion' => [
                'required',
                'string',
                'max:500',
                Rule::unique('mano_obras', 'descripcion')->ignore($this->manoObraCodigo, 'codigo')
            ]
        ], [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.max' => 'El código no puede exceder los 255 caracteres.',
            'codigo.unique' => 'El código ya está registrado.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'descripcion.unique' => 'La descripción ya está registrada.',
        ]);

        try {
            ManoObra::updateOrCreate(
                ['codigo' => $this->manoObraCodigo],
                ['codigo' => $this->codigo, 'descripcion' => $this->descripcion]
            );

            $this->alert('success', 'Datos guardados correctamente.');
            $this->mostrarFormularioManoObra = false;
            $this->obtenerManoObras();
            $this->reset('manoObraCodigo', 'codigo', 'descripcion');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function eliminarManoObra($codigo)
    {

        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarManoObra',
            'data' => [
                'codigo' => $codigo,
            ],
        ]);
    }
    public function confirmarEliminarManoObra($data)
    {
        $codigo = $data['codigo'];

        try {
            $manoObra = ManoObra::where('codigo', $codigo)->firstOrFail();
            $manoObra->delete();
            $this->alert('success', 'Registro eliminado correctamente.');
            $this->obtenerManoObras();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-campo.mano-obra-component');
    }
}