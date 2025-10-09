<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\CuaGrupo;
use App\Services\Cuadrilla\GrupoServicio;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaGrupoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioGrupoCuadrilla = false;
    public $grupoId;
    public $nombre;
    public $codigo;
    public $color;
    public $modalidad_pago = 'semanal';
    public $costo_dia_sugerido;
    protected $listeners = ['registrarGrupo','editarGrupo'];

    public function registrarGrupo()
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->mostrarFormularioGrupoCuadrilla = true;
    }
    public function editarGrupo($codigo){
        $this->resetForm();        
        $grupo = CuaGrupo::where('codigo',$codigo)->first();
        if($grupo){
            $this->grupoId = $codigo;
            $this->nombre = $grupo->nombre;
            $this->codigo = $grupo->codigo;
            $this->color = $grupo->color;
            $this->modalidad_pago = $grupo->modalidad_pago;
            $this->costo_dia_sugerido = $grupo->costo_dia_sugerido;
            $this->mostrarFormularioGrupoCuadrilla = true;
        }else{
            $this->alert('error','El grupo ha dejado de existir');
        }
    }
    public function registrar()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => [
                'required',
                'string',
                'max:30',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/', // Solo texto, no iniciar con número, permite solo guion bajo
            ],
            'color' => 'nullable|string|max:7', // Formato hexadecimal
            'modalidad_pago' => 'required|string|in:semanal,quincenal,mensual', // Valores específicos
            'costo_dia_sugerido' => 'required|numeric|min:0', // Número positivo
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'codigo.required' => 'El código es obligatorio',
            'codigo.regex' => 'El código solo debe contener letras, números y guiones bajos, y no puede comenzar con un número',
            'modalidad_pago.required' => 'La modalidad de pago es obligatoria',
            'modalidad_pago.in' => 'La modalidad de pago no es válida',
            'costo_dia_sugerido.required' => 'El costo sugerido es obligatorio',
            'costo_dia_sugerido.numeric' => 'El costo debe ser un número',
        ]);

        $data = [
            'nombre' => mb_strtoupper($this->nombre),
            'codigo' => $this->codigo ? mb_strtoupper($this->codigo):null,
            'color' => $this->color,
            'modalidad_pago' => $this->modalidad_pago,
            'costo_dia_sugerido' => $this->costo_dia_sugerido,
        ];

        try {
            GrupoServicio::guardarGrupo($data, $this->grupoId);
            $this->alert('success', $this->grupoId ? 'Grupo actualizado' : 'Grupo registrado');
            $this->resetForm();
            $this->mostrarFormularioGrupoCuadrilla = false;
            $this->dispatch('grupoRegistrado', $data);
        } catch (Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function resetForm(){
        $this->reset(['grupoId','nombre','codigo','color','costo_dia_sugerido']);
        $this->modalidad_pago = 'semanal';
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupo-form-component');
    }
}
