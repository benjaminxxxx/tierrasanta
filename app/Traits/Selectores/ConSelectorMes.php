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
        $session = Session::get($this->dateSessionKey);

        // Al usar (int) o intval, '01' se convierte en 1 automáticamente
        $this->mes = (int) ($session['mes'] ?? now()->format('m'));
        $this->anio = (int) ($session['anio'] ?? now()->format('Y'));

        $this->despuesMesAnioModificado($this->anio, $this->mes);
    }

    /**
     * Retrocede un mes.
     */
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        // En lugar de format('m'), usa format('n') que devuelve el mes sin ceros iniciales
        $this->anio = (int) $fecha->format('Y');
        $this->mes = (int) $fecha->format('n');
        $this->refrescarSessionMesAnio();
    }
    /**
     * Avanza un mes.
     */
    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->anio = (int) $fecha->format('Y');
        $this->mes = (int) $fecha->format('m');
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
            'mes' => (int) $this->mes,
            'anio' => (int) $this->anio,
        ]);

        $this->despuesMesAnioModificado($this->anio, $this->mes);
    }

    /**
     * Método que DEBE implementar cada componente que use este Trait.
     * Aquí se define qué hacer cuando cambie el mes/año (p. ej. refrescar datos).
     */
    abstract protected function despuesMesAnioModificado(string $anio, string $mes);
}