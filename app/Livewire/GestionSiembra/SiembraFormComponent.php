<?php

namespace App\Livewire\GestionSiembra;

use App\Models\Siembra;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SiembraFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $siembra_id;
    public $fecha_siembra, $fecha_renovacion, $campo_nombre;
    protected $listeners = ['agregarSiembra','editarSiembra'];
   
    public function agregarSiembra(){
        $this->siembra_id = null;
        $this->reset(['siembra_id', 'fecha_siembra', 'fecha_renovacion', 'campo_nombre']);
        $this->mostrarFormulario = true;
    }
    public function storeSiembra()
    {
        $validatedData = $this->validate([
            'fecha_siembra' => 'required|date',
            'fecha_renovacion' => 'nullable|date|after_or_equal:fecha_siembra',
            'campo_nombre' => 'required|string|max:50|exists:campos,nombre',
            
        ], [
            'fecha_siembra.required' => 'La fecha de siembra es obligatoria.',
            'fecha_siembra.date' => 'Ingrese una fecha válida.',
            'fecha_renovacion.date' => 'Ingrese una fecha válida.',
            'fecha_renovacion.after_or_equal' => 'La fecha de limpieza debe ser posterior o igual a la fecha de siembra.',
            'campo_nombre.required' => 'El campo es obligatorio.',
            'campo_nombre.max' => 'El nombre del campo no puede superar los 50 caracteres.',
            'campo_nombre.exists' => 'El campo seleccionado no existe.',
            
        ]);

        try {
            if(trim($validatedData['fecha_renovacion'])==''){
                $validatedData['fecha_renovacion'] = null;
            }
            if ($this->siembra_id) {
                // Editar siembra
                $siembra = Siembra::findOrFail($this->siembra_id);
                $siembra->update($validatedData);
                $this->alert('success', 'Siembra actualizada correctamente');
            } else {
                // Crear nueva siembra
                Siembra::create($validatedData);
                $this->alert('success', 'Siembra registrada exitosamente');
            }
            $this->mostrarFormulario = false;
            $this->reset(['siembra_id', 'fecha_siembra', 'fecha_renovacion', 'campo_nombre']);
            $this->dispatch('siembraGuardada'); // Para refrescar la tabla si es necesario
        } catch (\Exception $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar o actualizar la siembra.');
        }
    }
    public function editarSiembra($id)
    {
        try {
            
            $this->reset(['siembra_id', 'fecha_siembra', 'fecha_renovacion', 'campo_nombre']);

            $siembra = Siembra::findOrFail($id);
            $this->siembra_id = $siembra->id;
            $this->fecha_siembra = $siembra->fecha_siembra;
            $this->fecha_renovacion = $siembra->fecha_renovacion;
            $this->campo_nombre = $siembra->campo_nombre;
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al editar la siembra.');
        }
    }
    public function render()
    {
        return view('livewire.gestion-siembra.siembra-form-component');
    }
}
