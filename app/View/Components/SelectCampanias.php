<?php

namespace App\View\Components;

use App\Models\CampoCampania;
use Illuminate\View\Component;

class SelectCampanias extends Component
{
    public $placeholder;
    public $label;
    public function __construct($placeholder = 'Seleccione la campaña',$label = 'Campaña')
    {
        $this->placeholder = $placeholder;
        $this->label = $label;
    }

    public function render()
    {
        return view('components.select-campanias-nombre', [
            'campanias' => CampoCampania::select('nombre_campania')
            ->distinct()
            ->orderBy('nombre_campania','desc')
            ->get()
        ]);
    }
}
