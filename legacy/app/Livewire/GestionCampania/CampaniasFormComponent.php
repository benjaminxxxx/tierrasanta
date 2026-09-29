<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Services\CrudCampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampaniasFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $campaniaId;
    public $tabActual;
    public $campania = [];
    protected $listeners = [ 'editarCampania'];
    public function mount(){
        $this->campania = [
            'campo'=>null
        ];
    }
    public function editarCampania(int $campaniaId,?string $tab = 'general')
    {
        $this->resetForm();

        $campania = CampoCampania::with('campo_model')->find($campaniaId);

        if (!$campania) {
            return;
        }

        $this->tabActual = $tab;

        $this->campaniaId = $campania->id;
        foreach ($campania->toArray() as $key => $value) {
            $this->campania[$key] = $value;
        }
        $this->campania['fecha_inicio'] = $campania->fecha_inicio->format('Y-m-d');
        $this->campania['fecha_fin'] = $campania->fecha_fin?->format('Y-m-d');
        $this->mostrarFormulario = true;
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['campaniaId', 'campania']);
    }
    public function guardarCampania()
    {
        $this->validate([
            'campania.campo' => 'required',
            'campania.nombre_campania' => 'required|string',
            'campania.area' => 'required|numeric|between:0,99999999.99',
            'campania.fecha_inicio' => 'required|date',
            'campania.fecha_fin' => 'nullable|date|after_or_equal:campania.fecha_inicio',
            'campania.variedad_tuna' => 'nullable|string|max:50',
            'campania.sistema_cultivo' => 'nullable|string|max:255',
            'campania.tipo_cambio' => 'nullable|numeric|between:0,99999999.99',
            'campania.pencas_x_hectarea' => 'nullable|numeric',
        ],
        [
            'campania.campo.required' => 'Debe seleccionar un campo.',

            'campania.nombre_campania.required' => 'El nombre de la campaña es obligatorio.',
            'campania.nombre_campania.string' => 'El nombre de la campaña debe ser texto.',

            'campania.area.required' => 'El área es obligatoria.',
            'campania.area.numeric' => 'El área debe ser un valor numérico.',
            'campania.area.between' => 'El área debe estar entre 0 y 99,999,999.99.',

            'campania.fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'campania.fecha_inicio.date' => 'La fecha de inicio no es válida.',

            'campania.fecha_fin.date' => 'La fecha de fin no es válida.',
            'campania.fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'campania.variedad_tuna.string' => 'La variedad de tuna debe ser texto.',
            'campania.variedad_tuna.max' => 'La variedad de tuna no debe exceder los 50 caracteres.',

            'campania.sistema_cultivo.string' => 'El sistema de cultivo debe ser texto.',
            'campania.sistema_cultivo.max' => 'El sistema de cultivo no debe exceder los 255 caracteres.',

            'campania.tipo_cambio.numeric' => 'El tipo de cambio debe ser un valor numérico.',
            'campania.tipo_cambio.between' => 'El tipo de cambio debe estar entre 0 y 99,999,999.99.',

            'campania.pencas_x_hectarea.numeric' => 'La cantidad de pencas por hectárea debe ser un valor numérico.',
        ]);

        try {
            $data = $this->campania;

            // Normalización mínima de UI
            $data['nombre_campania'] = mb_strtoupper($data['nombre_campania']);
            
            $campania = app(CrudCampaniaServicio::class)
                ->guardar($data, $this->campaniaId);

            $this->alert(
                'success',
                $this->campaniaId
                ? 'La campaña fue actualizada correctamente.'
                : 'La campaña fue registrada correctamente.'
            );

            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->dispatch('campaniaInsertada',$campania->toArray());

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
            $this->dispatch('log', $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.gestion-campania.campanias-form-component');
    }
}
