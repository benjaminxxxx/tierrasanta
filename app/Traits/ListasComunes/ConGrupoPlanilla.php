<?php

namespace App\Traits\ListasComunes;

use App\Models\PlanGrupo;

trait ConGrupoPlanilla
{
    public $gruposPlanilla = [];
    public function bootConGrupoPlanilla(){
        $this->obtenerGrupos();
    }
    protected function obtenerGrupos()
    {
        return $this->gruposPlanilla  = PlanGrupo::all();
    }
}