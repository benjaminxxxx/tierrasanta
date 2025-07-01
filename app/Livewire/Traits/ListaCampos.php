<?php

namespace App\Livewire\Traits;

use App\Models\Campo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

trait ListaCampos
{
    public $campos;

    public function cargarCampos()
    {
        $this->campos = Campo::orderBy('orden')->get()->pluck('nombre')->toArray();
    }
}