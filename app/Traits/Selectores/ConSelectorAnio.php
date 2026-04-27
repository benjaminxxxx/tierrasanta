<?php

namespace App\Traits\Selectores;

use Carbon\Carbon;
use Session;

trait ConSelectorAnio
{
    public $anio;

    /**
     * Clave base para guardar el estado de mes y año en sesión.
     * Puedes sobrescribirla en tu componente si deseas un identificador distinto.
     */
    protected $dateSessionKey = 'fecha_reporte_anio';

    /**
     * Inicializa el mes y año desde sesión o usa la fecha actual.
     * Debes llamar a este método desde `mount()` del componente.
     */
    public function inicializarMesAnio()
    {
        $session = Session::get($this->dateSessionKey);
        $this->anio = (int) ($session['anio'] ?? now()->format('Y'));

        $this->despuesAnioSeleccionado($this->anio);
    }

    /**
     * Retrocede un mes.
     */
    public function anioAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, 1, 1)->subYear();
        $this->anio = (int) $fecha->format('Y');
        $this->refrescarSessionMesAnio();
    }
    /**
     * Avanza un mes.
     */
    public function anioSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, 1, 1)->addYear();
        $this->anio = (int) $fecha->format('Y');
        $this->refrescarSessionMesAnio();
    }

    public function updatedAnio($nuevoAnio)
    {
        $this->refrescarSessionMesAnio();
    }

    /**
     * Guarda el mes y año en sesión y ejecuta el callback de actualización.
     */
    private function refrescarSessionMesAnio()
    {
        Session::put($this->dateSessionKey, [
            'anio' => (int) $this->anio,
        ]);

        $this->despuesAnioSeleccionado($this->anio);
    }

    /**
     * Método que DEBE implementar cada componente que use este Trait.
     * Aquí se define qué hacer cuando cambie el mes/año (p. ej. refrescar datos).
     */
    abstract protected function despuesAnioSeleccionado(string $anio);
}