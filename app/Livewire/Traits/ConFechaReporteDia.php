<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

trait ConFechaReporteDia
{
    /**
     * La propiedad pública para la fecha, que será bindeada en la vista.
     */
    public $fecha;

    /**
     * La llave que se usará para guardar la fecha en la sesión.
     * Puedes sobrescribir esta propiedad en tu componente si necesitas una llave diferente.
     */
    protected $dateSessionKey = 'fecha_reporte';

    /**
     * Inicializa la fecha desde la sesión o con la fecha actual.
     * Debes llamar a este método desde el `mount()` de tu componente.
     */
    public function inicializarFecha()
    {
        $this->fecha = Session::get($this->dateSessionKey, Carbon::now()->format('Y-m-d'));
        $this->despuesFechaModificada($this->fecha); // Llama al método de actualización al inicio también
    }

    /**
     * Retrocede la fecha en un día.
     */
    public function fechaAnterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        $this->refrescarSessionFecha();
    }

    /**
     * Avanza la fecha en un día.
     */
    public function fechaPosterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        $this->refrescarSessionFecha();
    }

    /**
     * Este "hook" de Livewire se dispara automáticamente cuando la propiedad $fecha cambia.
     */
    public function updatedFecha($nuevaFecha)
    {
        $this->refrescarSessionFecha();
    }

    /**
     * Centraliza la lógica de guardar en sesión y recargar los datos.
     */
    private function refrescarSessionFecha()
    {
        Session::put($this->dateSessionKey, $this->fecha);
        $this->despuesFechaModificada($this->fecha);
    }

    /**
     * Este es el método que tu componente DEBE implementar.
     * El Trait asume que existe un método para refrescar los datos.
     * Usar un método abstracto fuerza a que cualquier componente que use este Trait
     * implemente su propia lógica de actualización de datos.
     */
    abstract protected function despuesFechaModificada(string $newDate);
}