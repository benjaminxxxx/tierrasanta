<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

trait ConFechaReporte
{
    public $mes;
    public $anio;

    public function cargarFechaDesdeSession($claveMes = 'fecha_reporte_mes', $claveAnio = 'fecha_reporte_anio')
    {
        $this->mes = Session::get($claveMes, now()->format('m'));
        $this->anio = Session::get($claveAnio, now()->format('Y'));
    }
    public function actualizarSesionMes($valor)
    {
        if ($valor === null || $valor === '') {
            $this->mes = null;
            Session::forget('fecha_reporte_mes');
        } else {
            $this->mes = str_pad((int) $valor, 2, '0', STR_PAD_LEFT);
            Session::put('fecha_reporte_mes', $this->mes);
        }
    }
    public function actualizarSesionAnio($valor)
    {
        if ($valor === null || $valor === '') {
            $this->anio = null;
            Session::forget('fecha_reporte_anio');
        } else {
            $this->anio = (int) $valor;
            Session::put('fecha_reporte_anio', $this->anio);
        }
    }
    public function updatedMes($valor)
    {
        $this->actualizarSesionMes($valor);
    }

    public function updatedAnio($valor)
    {
        $this->actualizarSesionAnio($valor);
    }
}