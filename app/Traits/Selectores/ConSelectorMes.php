<?php

namespace App\Traits\Selectores;

use Carbon\Carbon;
use Session;

trait ConSelectorMes
{
    /**
     * Propiedades públicas para el mes y año, que serán bindeadas en la vista.
     */
    public $mes;
    public $anio;

    /**
     * Clave base para guardar el estado de mes y año en sesión.
     * Puedes sobrescribirla en tu componente si deseas un identificador distinto.
     */
    protected $dateSessionKey = 'fecha_reporte_mes';

    /**
     * Inicializa el mes y año desde sesión o usa la fecha actual.
     * Debes llamar a este método desde `mount()` del componente.
     */
    public function inicializarMesAnio()
    {
        $session = Session::get($this->dateSessionKey, []);

        $this->mes = $session['mes'] ?? now()->format('m');
        $this->anio = $session['anio'] ?? now()->format('Y');

        // Ejecutar lógica personalizada al iniciar
        $this->despuesMesAnioModificado($this->anio, $this->mes);
    }

    /**
     * Retrocede un mes.
     */
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->anio = $fecha->format('Y');
        $this->mes = $fecha->format('m');
        $this->refrescarSessionMesAnio();
    }

    /**
     * Avanza un mes.
     */
    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->anio = $fecha->format('Y');
        $this->mes = $fecha->format('m');
        $this->refrescarSessionMesAnio();
    }

    /**
     * Hooks automáticos de Livewire para detectar cambios manuales.
     */
    public function updatedMes($nuevoMes)
    {
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
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]);

        $this->despuesMesAnioModificado($this->anio, $this->mes);
    }

    /**
     * Método que DEBE implementar cada componente que use este Trait.
     * Aquí se define qué hacer cuando cambie el mes/año (p. ej. refrescar datos).
     */
    abstract protected function despuesMesAnioModificado(string $anio, string $mes);
}