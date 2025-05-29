<?php

namespace App\Livewire;

use App\Models\CategoriaPesticida;
use App\Models\Producto;
use App\Models\ProductoNutriente;
use App\Models\SunatTabla5TipoExistencia;
use App\Models\SunatTabla6CodigoUnidadMedida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
    public $categoria;
    public $codigo_tipo_existencia;
    public $codigo_unidad_medida;
    public $sunatTipoExistencias;
    public $sunatCodigoUnidadMedidas;

    public $porcentaje_nitrogeno;
    public $porcentaje_fosforo;
    public $porcentaje_potasio;
    public $porcentaje_calcio;
    public $porcentaje_magnesio;
    public $porcentaje_zinc;
    public $porcentaje_manganeso;
    public $porcentaje_hierro;

    public $categoria_pesticida;
    public $listaCategoriasPesticida = [];
    protected $listeners = ['EditarProducto', 'CrearProducto'];
    public function mount()
    {
        $this->sunatTipoExistencias = SunatTabla5TipoExistencia::all();
        $this->sunatCodigoUnidadMedidas = SunatTabla6CodigoUnidadMedida::all();
        $this->listaCategoriasPesticida = CategoriaPesticida::all();
        $this->resetearValoresDefecto();
    }

    public function updatedCategoria($valor)
    {
        if ($valor !== 'fertilizante') {
            // Resetear los porcentajes si no es fertilizante
            $this->reset([
                'porcentaje_nitrogeno',
                'porcentaje_fosforo',
                'porcentaje_potasio',
                'porcentaje_calcio',
                'porcentaje_magnesio',
                'porcentaje_zinc',
                'porcentaje_manganeso',
                'porcentaje_hierro',
            ]);
            return;
        }

        $this->listarPorcentajes();

    }
    public function listarPorcentajes()
    {
        // Si es fertilizante y se está editando
        if ($this->productoId) {
            $nutrientes = ProductoNutriente::where('producto_id', $this->productoId)
                ->pluck('porcentaje', 'nutriente_codigo');

            $this->porcentaje_nitrogeno = $nutrientes->get('N');
            $this->porcentaje_fosforo = $nutrientes->get('P');
            $this->porcentaje_potasio = $nutrientes->get('K');
            $this->porcentaje_calcio = $nutrientes->get('Ca');
            $this->porcentaje_magnesio = $nutrientes->get('Mg');
            $this->porcentaje_zinc = $nutrientes->get('Zn');
            $this->porcentaje_manganeso = $nutrientes->get('Mn');
            $this->porcentaje_hierro = $nutrientes->get('Fe');
        }
    }
    public function resetearValoresDefecto()
    {
        $this->reset([
            'nombre_comercial',
            'ingrediente_activo',
            'porcentaje_nitrogeno',
            'porcentaje_fosforo',
            'porcentaje_potasio',
            'porcentaje_calcio',
            'porcentaje_magnesio',
            'porcentaje_zinc',
            'porcentaje_manganeso',
            'porcentaje_hierro',
        ]);
        if ($this->sunatTipoExistencias->count() > 0) {
            $sunatTipoExistencia = $this->sunatTipoExistencias->first();
            $this->codigo_tipo_existencia = $sunatTipoExistencia->codigo;
        }
        if ($this->sunatCodigoUnidadMedidas->count() > 0) {
            $sunatCodigoUnidadMedida = $this->sunatCodigoUnidadMedidas->first();
            $this->codigo_unidad_medida = $sunatCodigoUnidadMedida->codigo;
        }
    }
    protected function rules()
    {
        return [
            'ingrediente_activo' => 'nullable',
            'categoria' => 'required',
            'codigo_tipo_existencia' => 'required',
            'codigo_unidad_medida' => 'required',
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
        'categoria.required' => 'La categoría es obligatoria.',
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
            $this->categoria = $producto->categoria;
            $this->codigo_tipo_existencia = $producto->codigo_tipo_existencia;
            $this->codigo_unidad_medida = $producto->codigo_unidad_medida;
            $this->categoria_pesticida = $producto->categoria_pesticida;
            $this->mostrarFormulario = true;

            $this->listarPorcentajes();
        }
    }
    public function store()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $data = [
                'nombre_comercial' => mb_strtoupper(trim($this->nombre_comercial)),
                'ingrediente_activo' => mb_strtoupper(trim($this->ingrediente_activo)),
                'categoria' => $this->categoria,
                'codigo_tipo_existencia' => $this->codigo_tipo_existencia,
                'codigo_unidad_medida' => $this->codigo_unidad_medida
            ];

            if ($this->categoria == 'pesticida') {
                $data['categoria_pesticida'] = $this->categoria_pesticida ?? null;
            } else {
                $data['categoria_pesticida'] = null;
            }

            if ($this->productoId) {
                $producto = Producto::find($this->productoId);

                if ($producto) {
                    $producto->update($data);
                    $productoId = $producto->id;

                    // Si no es fertilizante, eliminar nutrientes existentes
                    if ($this->categoria !== 'fertilizante') {
                        ProductoNutriente::where('producto_id', $productoId)->delete();
                    }
                }
            } else {
                $producto = Producto::create($data);
                $productoId = $producto->id;
            }

            // Si es fertilizante, registrar los nutrientes válidos
            if ($this->categoria === 'fertilizante') {
                // Eliminar anteriores si existe
                ProductoNutriente::where('producto_id', $productoId)->delete();

                $nutrientes = [
                    'N' => $this->porcentaje_nitrogeno,
                    'P' => $this->porcentaje_fosforo,
                    'K' => $this->porcentaje_potasio,
                    'Ca' => $this->porcentaje_calcio,
                    'Mg' => $this->porcentaje_magnesio,
                    'Zn' => $this->porcentaje_zinc,
                    'Mn' => $this->porcentaje_manganeso,
                    'Fe' => $this->porcentaje_hierro,
                ];

                foreach ($nutrientes as $codigo => $valor) {
                    if (!is_null($valor) && $valor != 0 && trim($valor) != '') {
                        ProductoNutriente::create([
                            'producto_id' => $productoId,
                            'nutriente_codigo' => $codigo,
                            'porcentaje' => $valor,
                        ]);
                    }
                }
            }

            DB::commit();

            $this->alert('success', $this->productoId ? 'Registro actualizado exitosamente.' : 'Registro creado exitosamente.');



            $this->resetearValoresDefecto();
            $this->dispatch('ActualizarProductos');
            $this->closeForm();

        } catch (QueryException $e) {
            DB::rollBack();
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
