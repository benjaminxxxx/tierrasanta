<?php

namespace App\Traits\ListasComunes;

use App\Models\PlanTipoAsistencia;

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
        $this->tipoAsistencias = PlanTipoAsistencia::all();

        $this->tipoAsistenciasCodigos = array_merge(
            [''],
            $this->tipoAsistencias->pluck('codigo')->toArray()
        );

        $this->tipoAsistenciasHoras = PlanTipoAsistencia::get(['codigo', 'horas_jornal'])
            ->pluck('horas_jornal', 'codigo')
            ->toArray();
    }
}
