<?php

namespace App\Livewire;

use App\Models\Configuracion;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

/**
 * Componente Livewire para la gestión de configuraciones de la aplicación.
 */
class ConfiguracionComponent extends Component
{
    use LivewireAlert;

    public $configuracionesObjeto;
    public $configuraciones;

    public function mount()
    {
        $this->loadConfiguraciones();
    }

    public function render()
    {
        return view('livewire.configuracion-component');
    }

    public function save()
    {
        foreach ($this->configuraciones as $codigo => $valor) {
            Configuracion::where('codigo', $codigo)->update(['valor' => $valor]);
        }

        $this->alert('success', 'Configuraciones actualizadas con éxito.');
    }

    protected function loadConfiguraciones()
    {
        $this->configuracionesObjeto = Configuracion::all();
        $this->configuraciones = $this->configuracionesObjeto->pluck('valor', 'codigo')->toArray();
    }
}
