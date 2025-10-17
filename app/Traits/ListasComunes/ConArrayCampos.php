<?php

namespace App\Traits\ListasComunes;

use App\Models\Campo;

trait ConArrayCampos
{
    public $campos = [];

    /**
     * Método bootable para inicializar automáticamente
     * la lista de campos cuando el trait es usado en un componente.
     */
    public function bootConArrayCampos()
    {
        $this->obtenerListaCampos();
    }

    /**
     * Carga la lista de campos en orden alfabético, con un valor vacío inicial.
     */
    protected function obtenerListaCampos()
    {
        $this->campos = [''];

        $camposNuevos = Campo::orderBy('nombre')
            ->get(['nombre'])
            ->pluck('nombre')
            ->toArray();

        $this->campos = array_merge($this->campos, $camposNuevos);
    }
}
