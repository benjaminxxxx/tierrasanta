<?php

namespace App\View\Components;

use App\Models\Campo;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectCampo extends Component
{
    public $placeholder;
    public $label;
    public function __construct($placeholder = 'Seleccione el campo',$label = 'Campo')
    {
        $this->placeholder = $placeholder;
        $this->label = $label;
    }

    public function render()
    {
        return view('components.select-campo', [
            'campos' => Campo::listar(), // Obtiene la lista de campos
        ]);
    }
}
