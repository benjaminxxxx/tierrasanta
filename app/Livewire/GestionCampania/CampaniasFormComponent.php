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
    protected $listeners = ['registroCampania', 'editarCampania'];
    public function mount(){
        $this->campania = [
            'campo'=>null
        ];
    }
    public function registroCampania()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
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
        ]);

        try {
            $data = $this->campania;

            // Normalización mínima de UI
            $data['nombre_campania'] = mb_strtoupper($data['nombre_campania']);

            app(CrudCampaniaServicio::class)
                ->guardar($data, $this->campaniaId);

            $this->alert(
                'success',
                $this->campaniaId
                ? 'La campaña fue actualizada correctamente.'
                : 'La campaña fue registrada correctamente.'
            );

            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->dispatch('campaniaInsertada');

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
