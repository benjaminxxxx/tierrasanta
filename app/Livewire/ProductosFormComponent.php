<?php

namespace App\Livewire;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProductosFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $productoId;

    public $nombre_comercial;
    public $ingrediente_activo;
    public $unidad_medida;
    public $categoria_id;
    public $categorias;

    protected $listeners = ['EditarProducto','CrearProducto'];
    public function mount(){
        $this->categorias = CategoriaProducto::all();
        $this->resetearValoresDefecto();
    }
    public function resetearValoresDefecto(){
        $this->unidad_medida = 'KG';
        
        if($this->categorias->count()>0){
            $categoria = CategoriaProducto::first();
            $this->categoria_id = $categoria->id;
        }
    }
    protected function rules()
    {
        return [
            'ingrediente_activo' => 'nullable',
            'unidad_medida'=>'required',
            'categoria_id'=>'required|integer',
            'nombre_comercial' => [
                'required',
                'string',
                Rule::unique('productos', 'nombre_comercial')->ignore($this->productoId),
            ]
        ];
    }

    protected $messages = [
        'ingrediente_activo.required' => 'El nombre del producto es obligatorio.',
        'ingrediente_activo.unique' => 'El nombre del producto ya está en uso.',
        'categoria_id.required' => 'La categoría es obligatoria.',
        'unidad_medida.required' => 'La unidad de medida es obligatoria.',
    ];

    public function CrearProducto()
    {
        $this->reset([
            'nombre_comercial',
            'ingrediente_activo'
        ]);
        $this->resetearValoresDefecto();
        $this->mostrarFormulario = true;
    }
    public function EditarProducto($id)
    {
        $producto = Producto::find($id);
        if ($producto) {
            $this->productoId = $producto->id;
            $this->nombre_comercial = $producto->nombre_comercial;
            $this->ingrediente_activo = $producto->ingrediente_activo;
            $this->unidad_medida = $producto->unidad_medida;
            $this->categoria_id = $producto->categoria_id;
            $this->mostrarFormulario = true;
        }
    }
    public function store()
    {
        $this->validate();

        try {
            $data = [
                'nombre_comercial' => mb_strtoupper(trim($this->nombre_comercial)),
                'ingrediente_activo' => mb_strtoupper(trim($this->ingrediente_activo)),
                'unidad_medida' => mb_strtoupper(trim($this->unidad_medida)),
                'categoria_id' => mb_strtoupper($this->categoria_id),
            ];

            if ($this->productoId) {
                $producto = Producto::find($this->productoId);
                if ($producto) {
                    $producto->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                Producto::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar
            $this->reset([
                'nombre_comercial',
                'ingrediente_activo'
            ]);

            $this->resetearValoresDefecto();
            $this->dispatch('ActualizarProductos');
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
        return view('livewire.productos-form-component');
    }
}
