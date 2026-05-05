<?php

namespace App\Livewire\GestionUsuario;

use App\Services\Sistema\PermisosServicio;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PermisosRolComponent extends Component
{
    use LivewireAlert;

    public string $rolNombre = '';
    public array $arbol = [];
    // Array plano de nombres de permisos activados
    public array $permisosActivados = [];

    public function mount(string $rol): void
    {
        $this->rolNombre = $rol;
        $this->arbol = config('permisos_tree');
        $this->permisosActivados = PermisosServicio::obtenerPermisosDeRol($rol);
    }

    public function guardar(): void
    {
        try {
            PermisosServicio::guardarPermisosParaRol($this->rolNombre, $this->permisosActivados);
            $this->alert('success', 'Permisos actualizados correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Error: ' . $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-usuario.permisos-rol-component');
    }
}