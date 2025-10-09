<?php

namespace App\Traits\ListasComunes;

use App\Models\CuaGrupo;

trait ConGrupoCuadrilla
{
    public $grupoCuadrillas = [];
    public function bootConGrupoCuadrilla(){
        $this->obtenerGrupos();
    }
    protected function obtenerGrupos()
    {
        return $this->grupoCuadrillas  = CuaGrupo::all();
    }
}