<?php

namespace App\Livewire\GestionSiembra;

use App\Models\Siembra;
use App\Services\SiembraServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SiembraFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $siembra_id;
    public $fecha_siembra;
    public $campo_nombre;
    protected $listeners = ['agregarSiembra'];

    public function agregarSiembra()
    {
        $this->siembra_id = null;
        $this->reset(['siembra_id', 'fecha_siembra', 'campo_nombre']);
        $this->mostrarFormulario = true;
    }

    public function storeSiembra()
    {
        $validatedData = $this->validate([
            'fecha_siembra' => 'required|date',
            'campo_nombre' => 'required|string|max:50|exists:campos,nombre',
        ], [
            'fecha_siembra.required' => 'La fecha de siembra es obligatoria.',
            'fecha_siembra.date' => 'Ingrese una fecha válida.',
            'campo_nombre.required' => 'El campo es obligatorio.',
            'campo_nombre.max' => 'El nombre del campo no puede superar los 50 caracteres.',
            'campo_nombre.exists' => 'El campo seleccionado no existe.',
        ]);

        try {
            if ($this->siembra_id) {
                SiembraServicio::actualizar($this->siembra_id, $validatedData);
                $this->alert('success', 'Siembra actualizada correctamente');
            } else {
                SiembraServicio::crear($validatedData);
                $this->alert('success', 'Siembra registrada exitosamente');
            }

            $this->mostrarFormulario = false;
            $this->reset(['siembra_id', 'fecha_siembra', 'campo_nombre']);
            $this->dispatch('siembraGuardada');
        } catch (\Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar o actualizar la siembra.');
        }
    }

    public function render()
    {
        return view('livewire.gestion-siembra.siembra-form-component');
    }
}