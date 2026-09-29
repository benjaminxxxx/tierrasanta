<?php

namespace App\Livewire\Campo;

use App\Models\Maquinaria;
use App\Models\Producto;
use App\Support\ImagenHelper;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class MaquinariaFormComponent extends Component
{
    use LivewireAlert, WithFileUploads;

    public $nombre;
    public $alias_blanco;
    public $combustible_producto_id;
    public $usa_distribucion = true;
    public $consumo_modo;
    public $consumo_estimado;
    public $placa;
    public $foto;            // archivo nuevo (temporal de Livewire)
    public $fotoActual;      // ruta guardada
    public $quitarFoto = false;
    public $mostrarFormulario = false;
    public $maquinaria_id;
    public array $combustibles = [];

    protected $listeners = ['EditarMaquinaria', 'RegistrarMaquinaria'];

    protected function rules()
    {
        return [
            'nombre' => 'required',
            'alias_blanco' => 'required',
            'combustible_producto_id' => 'nullable|exists:productos,id',
            'usa_distribucion' => 'boolean',
            'consumo_modo' => 'nullable|in:' . implode(',', array_keys(Maquinaria::MODOS_CONSUMO)),
            'consumo_estimado' => 'nullable|required_with:consumo_modo|numeric|gt:0',
            'placa' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la maquinaria es obligatorio.',
        'alias_blanco.required' => 'El nombre alias es obligatorio para su uso en Kardex blanco.',
        'consumo_estimado.required_with' => 'Indica el consumo estimado para el modo elegido.',
        'foto.image' => 'La foto debe ser una imagen.',
        'foto.max' => 'La foto no puede pesar más de 8 MB.',
    ];

    public function mount()
    {
        $this->combustibles = Producto::where('categoria_codigo', 'combustible')
            ->orderBy('nombre_comercial')->pluck('nombre_comercial', 'id')->toArray();
    }

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
            $this->combustible_producto_id = $maquinaria->combustible_producto_id;
            $this->usa_distribucion = (bool) $maquinaria->usa_distribucion;
            $this->consumo_modo = $maquinaria->consumo_modo;
            $this->consumo_estimado = $maquinaria->consumo_estimado;
            $this->placa = $maquinaria->placa;
            $this->fotoActual = $maquinaria->foto;
            $this->mostrarFormulario = true;
        }
    }

    public function store()
    {
        $this->validate();

        try {
            $data = [
                'nombre' => mb_strtoupper(trim($this->nombre)),
                'alias_blanco' => mb_strtoupper(trim($this->alias_blanco)),
                'combustible_producto_id' => $this->combustible_producto_id ?: null,
                'usa_distribucion' => (bool) $this->usa_distribucion,
                'consumo_modo' => $this->consumo_modo ?: null,
                'consumo_estimado' => $this->consumo_modo ? $this->consumo_estimado : null,
                'placa' => $this->placa ? mb_strtoupper(trim($this->placa)) : null,
            ];

            // Foto: se guarda ajustada (sin deformar, lado mayor 1024 px); la anterior se borra
            if ($this->foto) {
                $data['foto'] = ImagenHelper::guardarAjustada($this->foto, 'maquinarias');
            } elseif ($this->quitarFoto) {
                $data['foto'] = null;
            }
            if (array_key_exists('foto', $data) && $this->fotoActual) {
                Storage::disk('public')->delete($this->fotoActual);
            }

            if ($this->maquinaria_id) {
                Maquinaria::findOrFail($this->maquinaria_id)->update($data);
                $this->alert('success', 'Registro actualizado exitosamente.');
            } else {
                Maquinaria::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            $this->resetForm();
            $this->dispatch('ActualizarMaquinarias');
            $this->closeForm();
        } catch (\Throwable $e) {
            $this->alert('error', 'No se pudo guardar: ' . $e->getMessage());
        }
    }

    public function closeForm()
    {
        $this->mostrarFormulario = false;
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['nombre', 'alias_blanco', 'maquinaria_id', 'combustible_producto_id', 'consumo_modo',
            'consumo_estimado', 'placa', 'foto', 'fotoActual', 'quitarFoto']);
        $this->usa_distribucion = true;
    }

    public function render()
    {
        // Unidad del combustible elegido para las etiquetas del consumo (ej. GAL)
        $unidad = 'unidad';
        if ($this->combustible_producto_id) {
            $tabla6 = Producto::with('tabla6')->find($this->combustible_producto_id)?->tabla6;
            $unidad = $tabla6?->alias ?: ($tabla6?->descripcion ?: 'unidad');
        }

        return view('livewire.campo.maquinaria-form-component', compact('unidad'));
    }
}
