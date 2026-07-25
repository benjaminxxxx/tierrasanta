<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanCargo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CargoFormComponent extends Component
{
    use LivewireAlert;
    public ?int $cargoId = null;
    public bool $mostrarModal = false;

    public string $nombre = '';
    public ?int $cupoMaximo = null;
    public bool $activo = true;
    protected $listeners = ['crearCargo', 'editarCargo'];

    public function crearCargo()
    {
        $this->reset(['cargoId','nombre','cupoMaximo','activo']);
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }
    public function editarCargo($cargoId)
    {
        $this->cargoId = $cargoId;
        $this->reset(['nombre','cupoMaximo','activo']);
        $this->resetErrorBag();

        if ($this->cargoId) {
            $cargo = PlanCargo::findOrFail($this->cargoId);
            $this->nombre = $cargo->nombre;
            $this->cupoMaximo = $cargo->cupo_maximo;
            $this->activo = $cargo->activo;
            $this->mostrarModal = true;
        }else{
            $this->alert('error','Registro inexistente');
        }
    }
    public function mount(): void
    {

    }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:150',
                function ($attribute, $value, $fail) {
                    $slug = PlanCargo::normalizar($value);
                    $existe = PlanCargo::where('slug', $slug)
                        ->when($this->cargoId, fn($q) => $q->where('id', '!=', $this->cargoId))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un cargo con un nombre equivalente.');
                    }
                },
            ],
            'cupoMaximo' => ['nullable', 'integer', 'min:1'],
            'activo' => ['boolean'],
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $datos = [
                'nombre' => trim($this->nombre),
                'cupo_maximo' => $this->cupoMaximo,
                'activo' => $this->activo,
            ];

            if ($this->cargoId) {
                PlanCargo::findOrFail($this->cargoId)->update($datos);
                $mensaje = 'Cargo actualizado.';
            } else {
                PlanCargo::create($datos);
                $mensaje = 'Cargo creado.';
            }

            $this->alert('success', $mensaje);
            $this->dispatch('cargoGuardado');
            $this->mostrarModal = false;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }


    }

    public function render()
    {
        return view('livewire.gestion-planilla.cargo-form-component');
    }
}