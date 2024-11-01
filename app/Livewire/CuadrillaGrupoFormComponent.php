<?php

namespace App\Livewire;

use App\Models\CuaGrupo;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaGrupoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $grupoId;
    public $nombre;
    public $codigo;
    public $color;
    public $modalidad_pago = 'semanal';
    public $costo_dia_sugerido;
    protected $listeners = ['registrarGrupo','editarGrupo'];

    public function registrarGrupo()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function editarGrupo($codigo){
        $this->resetForm();        
        $this->grupoId = $codigo;
        $grupo = CuaGrupo::where('codigo',$this->grupoId)->first();
        if($grupo){
            
            $this->nombre = $grupo->nombre;
            $this->codigo = $grupo->codigo;
            $this->color = $grupo->color;
            $this->modalidad_pago = $grupo->modalidad_pago;
            $this->costo_dia_sugerido = $grupo->costo_dia_sugerido;
        }
        $this->mostrarFormulario = true;
    }
    public function registrar()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/', // Solo texto, no iniciar con número, permite solo guion bajo
            ],
            'color' => 'required|string|max:7', // Formato hexadecimal
            'modalidad_pago' => 'required|string|in:semanal,quincenal,mensual', // Valores específicos
            'costo_dia_sugerido' => 'required|numeric|min:0', // Número positivo
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'codigo.required' => 'El código es obligatorio',
            'codigo.regex' => 'El código solo debe contener letras, números y guiones bajos, y no puede comenzar con un número',
            'color.required' => 'El color es obligatorio',
            'modalidad_pago.required' => 'La modalidad de pago es obligatoria',
            'modalidad_pago.in' => 'La modalidad de pago no es válida',
            'costo_dia_sugerido.required' => 'El costo sugerido es obligatorio',
            'costo_dia_sugerido.numeric' => 'El costo debe ser un número',
        ]);

        $data = [
            'nombre' => mb_strtoupper($this->nombre),
            'codigo' => mb_strtoupper($this->codigo),
            'color' => $this->color,
            'modalidad_pago' => $this->modalidad_pago,
            'costo_dia_sugerido' => $this->costo_dia_sugerido,
        ];

        try {
            
            if ($this->grupoId) {
                // Actualizar registro existente
                CuaGrupo::where('codigo', $this->grupoId)->update($data);
                $this->alert('success', 'Grupo de Cuadrilla actualizado exitosamente.');
            } else {
                // Crear nuevo registro
                $existe = CuaGrupo::where('codigo', $this->codigo)->exists();
                if($existe){
                    throw new Exception("El código ya esta siendo usado");                    
                }
                CuaGrupo::create($data);
                $this->alert('success', 'Grupo de Cuadrilla registrado exitosamente.');
            }

            // Cerrar el formulario y resetear campos
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->dispatch('grupoRegistrado', $data);
        } catch (Exception $e) {
            $this->alert('error', "Ocurrió un error interno, asegurese que el código no sea duplicado.");
        } catch (QueryException $e) {
            $this->alert('error', 'Hubo un error al guardar el grupo: ' . $e->getMessage());
        }
    }
    public function resetForm(){
        $this->grupoId = null;
        $this->nombre = null;
        $this->codigo = null;
        $this->color = null;
        $this->modalidad_pago = 'semanal';
        $this->costo_dia_sugerido = null;
    }
    public function render()
    {
        return view('livewire.cuadrilla-grupo-form-component');
    }
}
