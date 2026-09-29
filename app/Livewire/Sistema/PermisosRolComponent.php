<?php

namespace App\Livewire\Sistema;

use Livewire\Attributes\Title;
use App\Services\Sistema\PermisosServicio;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Permisos por Rol')]
class PermisosRolComponent extends Component
{
    use LivewireAlert;

    public $breadcrumb = [];
    public string $rolNombre = '';
    public array $arbol = [];
    // Array plano de nombres de permisos activados
    public array $permisosActivados = [];

    public function mount(string $rol): void
    {
        $this->rolNombre = $rol;
        $this->breadcrumb = [
            ['route' => 'sistema.roles', 'label' => 'Roles'],
            ['label' => $this->rolNombre]
        ];
        $this->arbol = config('permisos_tree');
        $this->permisosActivados = PermisosServicio::obtenerPermisosDeRol($rol);
    }

    public function guardarPermisos(): void
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
        return view('livewire.sistema.permisos-rol-component');
    }
}