<?php
namespace App\Livewire\GestionPlanilla;

use App\Services\Planilla\CalculoVacacionesServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CalculoVacacionesModalComponent extends Component
{
    use LivewireAlert;
    public bool $mostrarModal = false;
    public int $mes;
    public int $anio;
    public array $filas = [];

    protected $listeners = ['abrirCalculoVacaciones' => 'abrir'];

    public function abrir(int $mes, int $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->filas = app(CalculoVacacionesServicio::class)->obtenerSuspensionesVacacionalesDelMes($mes, $anio);
        $this->mostrarModal = true;
    }

    public function calcularTodos()
    {
        $servicio = app(CalculoVacacionesServicio::class);

        $this->filas = collect($this->filas)->map(function ($fila) use ($servicio) {
          
            $fila['vacaciones_neto_pagadas'] = $servicio->calcularMonto(
                $fila['pago_jornal_diario'],
                $fila['dias_habiles']
            );
            return $fila;
        })->toArray();
    }

    public function guardar()
    {
        app(CalculoVacacionesServicio::class)->guardarCalculos($this->mes, $this->anio, $this->filas);

        $this->mostrarModal = false;
        $this->dispatch('vacacionesCalculadas'); // el componente padre escucha esto y recarga la grilla

        $this->alert('success', 'Vacaciones calculadas y guardadas.');
    }

    public function render()
    {
        return view('livewire.gestion-planilla.calculo-vacaciones-modal-component');
    }
}