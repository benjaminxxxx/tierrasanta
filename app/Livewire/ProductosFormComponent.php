<?php

namespace App\Livewire;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\SunatTabla5TipoExistencia;
use App\Models\SunatTabla6CodigoUnidadMedida;
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
    public $categoria_id;
    public $categorias;
    public $codigo_tipo_existencia;
    public $codigo_unidad_medida;
    public $sunatTipoExistencias;
    public $sunatCodigoUnidadMedidas;

    protected $listeners = ['EditarProducto','CrearProducto'];
    public function mount(){
        $this->categorias = CategoriaProducto::all();
        $this->sunatTipoExistencias = SunatTabla5TipoExistencia::all();
        $this->sunatCodigoUnidadMedidas = SunatTabla6CodigoUnidadMedida::all();
        $this->resetearValoresDefecto();
    }
    public function resetearValoresDefecto(){
        
        if($this->categorias->count()>0){
            $categoria = CategoriaProducto::first();
            $this->categoria_id = $categoria->id;
        }
        if($this->sunatTipoExistencias->count()>0){
            $sunatTipoExistencia = $this->sunatTipoExistencias->first();
            $this->codigo_tipo_existencia = $sunatTipoExistencia->codigo;
        }
        if($this->sunatCodigoUnidadMedidas->count()>0){
            $sunatCodigoUnidadMedida = $this->sunatCodigoUnidadMedidas->first();
            $this->codigo_unidad_medida = $sunatCodigoUnidadMedida->codigo;
        }
    }
    protected function rules()
    {
        return [
            'ingrediente_activo' => 'nullable',
            'categoria_id'=>'required|integer',
            'codigo_tipo_existencia'=>'required',
            'codigo_unidad_medida'=>'required',
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
        'codigo_tipo_existencia.required' => 'El tipo de asistencia es obligatorio.',
        'codigo_unidad_medida.required' => 'El código de unidad es obligatorio.',
    ];

    public function CrearProducto()
    {
        $this->resetErrorBag();
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
            $this->categoria_id = $producto->categoria_id;
            $this->codigo_tipo_existencia = $producto->codigo_tipo_existencia;
            $this->codigo_unidad_medida = $producto->codigo_unidad_medida;
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
                'categoria_id' => mb_strtoupper($this->categoria_id),
                'codigo_tipo_existencia'=>$this->codigo_tipo_existencia,
                'codigo_unidad_medida'=>$this->codigo_unidad_medida
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
