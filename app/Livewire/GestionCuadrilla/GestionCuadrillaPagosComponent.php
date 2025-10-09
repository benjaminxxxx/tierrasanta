<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuaGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\Cuadrilla\PagoServicio;
use App\Exports\Cuadrilla\PagosCuadrillaExport;
use Maatwebsite\Excel\Facades\Excel;

class GestionCuadrillaPagosComponent extends Component
{
    use LivewireAlert;
    public $fecha_inicio;
    public $fecha_fin;
    public $grupoSeleccionado;
    public $nombre_cuadrillero;
    public $grupos = [];
    public $registros;
    public $header;
    public function mount()
    {
        $this->grupoSeleccionado = Session::get('grupo_seleccionado');
        $this->grupos = CuaGrupo::all();
        $this->fecha_inicio = Session::get('fecha_inicio', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $this->fecha_fin = Session::get('fecha_fin', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $this->buscarRegistros();
    }
    public function updatedGrupoSeleccionado($valor)
    {
        Session::put('grupo_seleccionado', $valor);
    }
    public function updatedFechaInicio($valor)
    {
        Session::put('fecha_inicio', $valor);
    }
    public function updatedFechaFin($valor)
    {
        Session::put('fecha_fin', $valor);
    }
    public function generarReportePagosCuadrilla()
    {
        try {
            $pagos = app(PagoServicio::class)->obtenerPagosPorRango($this->fecha_inicio, $this->fecha_fin, $this->grupoSeleccionado);
            //dd($pagos);
            return Excel::download(new PagosCuadrillaExport($pagos), 'reporte_pagos_cuadrilla.xlsx');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registrarPago($listaPagos)
    {
        try {
            CuadrilleroServicio::registrarPagos($listaPagos, $this->fecha_inicio, $this->fecha_fin);
            $this->alert('success', 'Registros con pagos procesados exitosamente.');
            $this->buscarRegistros();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function buscarRegistros()
    {
        $this->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ], [
            'fecha_inicio.required' => 'Debe seleccionar una fecha',
            'fecha_fin.required' => 'Debe seleccionar una fecha',
        ]);
        try {
            $datosRegistrosPago = CuadrilleroServicio::obtenerHandsonTablePagoCuadrilla(
                $this->fecha_inicio,
                $this->fecha_fin,
                $this->grupoSeleccionado,
                $this->nombre_cuadrillero
            );
            $inicio = Carbon::parse($this->fecha_inicio);
            $fin = Carbon::parse($this->fecha_fin);
            $periodo = CarbonPeriod::create($inicio, $fin);

            $diasSemana = [
                0 => 'Dom',
                1 => 'Lun',
                2 => 'Mar',
                3 => 'Mié',
                4 => 'Jue',
                5 => 'Vie',
                6 => 'Sáb',
            ];

            $header = [];
            $index = 1;

            foreach ($periodo as $fecha) {
                $dia = $diasSemana[$fecha->dayOfWeek]; // 0 = domingo, 1 = lunes, ...
                $label = "{$dia} {$fecha->day}";
                $header[] = [
                    'keyIndex' => $index,
                    'label' => $label,
                ];
                $index++;
            }
            $this->registros = $datosRegistrosPago;
            $this->header = $header;
            $this->dispatch('cargarRegistroPagos', $datosRegistrosPago, $header);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-pagos-component');
    }
}
