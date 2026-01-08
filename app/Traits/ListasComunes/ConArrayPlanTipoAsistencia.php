<?php

namespace App\Traits\ListasComunes;

use App\Models\PlanTipoAsistencia;
use App\Services\PlanTipoAsistenciaServicio;

trait ConArrayPlanTipoAsistencia
{
    public $tipoAsistencias = [];
    public $tipoAsistenciasCodigos = [];
    public $tipoAsistenciasHoras = [];

    /**
     * Método bootable: si el trait se usa en un componente Livewire,
     * este método se ejecuta automáticamente al inicializarlo.
     */
    public function bootConArrayPlanTipoAsistencia()
    {
        $this->obtenerListasTipoAsistencia();
    }

    /**
     * Carga las listas de tipo de asistencia y sus atributos derivados.
     */
    protected function obtenerListasTipoAsistencia()
    {
        $servicio = app(PlanTipoAsistenciaServicio::class);

        $this->tipoAsistencias = $servicio->listarTodos();
        $this->tipoAsistenciasCodigos = $servicio->obtenerCodigosParaSelector();
        $this->tipoAsistenciasHoras = $servicio->obtenerMapaHoras();
    }
}
