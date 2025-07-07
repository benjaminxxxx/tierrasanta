<?php

namespace App\View\Components;

use App\Models\Campo;
use Illuminate\View\Component;

class SelectCampo extends Component
{
    public $placeholder;
    public $label;

    public function __construct($placeholder = 'Seleccione el campo', $label = 'Campo')
    {
        $this->placeholder = $placeholder;
        $this->label = $label;
    }

    public function render()
    {
        // Transformamos los campos para el formato que espera x-searchable-select
        $campos = Campo::all()->map(fn ($campo) => [
            'id' => $campo->nombre,
            'name' => $campo->nombre,
        ])->toArray();

        return view('components.select-campo', [
            'campos' => $campos,
        ]);
    }
}

