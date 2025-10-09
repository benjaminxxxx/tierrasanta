<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadResumenPorTramo;
use App\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos;
use App\Services\Cuadrilla\TramoLaboral\ResumenTramoServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaReportePagoComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioReportePago = false;
    public $tituloReporte = '';
    public $resumenPorTramo;
    public $nombreCuadrilla = '-';
    public $pagarJornal = false;
    public $listaPago = [];
    public $listaCuadrilleros = [];
    public $periodo = [];
    public $fechaInicioPago;
    public $fechaFinPago;

    protected $listeners = ['abrirReportePagoPorTramo'];

    public function cambiarCondicionResumen($resumenId = null)
    {
        try {
            if (!$resumenId) {
                return;
            }

            $this->resumenPorTramo = app(ResumenTramoServicio::class)->cambiarCondicion($resumenId);
            $this->alert('success', 'Estado actualizado correctamente.');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function abrirReportePagoPorTramo($tramoResumenId)
    {
        try {
            $this->resumenPorTramo = CuadResumenPorTramo::findOrFail($tramoResumenId);
            $this->tituloReporte = $this->obtenerTitulo(
                $this->resumenPorTramo->modalidad_pago,
                $this->resumenPorTramo->fecha_acumulada,
                $this->resumenPorTramo->fecha_fin
            );

            $this->obtenerListaResumen();
            $this->nombreCuadrilla = $this->resumenPorTramo->grupo->nombre;
            $this->mostrarFormularioReportePago = true;
        } catch (\Throwable $th) {
            return $this->alert('error', $th->getMessage());
        }
    }
    public function cerrarModal()
    {
        $this->mostrarFormularioReportePago = false;
        $this->reset(['listaPago', 'periodo', 'pagarJornal']);
    }


    private function obtenerListaResumen()
    {

        $resultado = app(ListaAcumuladaTramos::class)->obtenerListaCuadrilleros($this->resumenPorTramo);
        $this->periodo = $resultado['periodo'];
        $this->listaPago = $resultado['listaPago'];
    }

    private function obtenerListaCuadrilleros($tramoLaboral, $grupoCodigo)
    {

        $cuadTramoLaboralGrupo = $tramoLaboral
            ->gruposEnTramos()
            ->where('codigo_grupo', $grupoCodigo)
            ->first();
        return $cuadTramoLaboralGrupo->cuadrilleros ?? [];

    }

    private function obtenerTitulo($modalidadPago, $fechaInicio, $fechaFin)
    {
        // Parsear fechas
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        // Convertir a formato: 8 DE SEPTIEMBRE
        $inicioTexto = mb_strtoupper($inicio->translatedFormat('j \d\e F'), 'UTF-8');
        $finTexto = mb_strtoupper($fin->translatedFormat('j \d\e F'), 'UTF-8');

        // Título final
        return 'CUADRILLA ' . mb_strtoupper($modalidadPago, 'UTF-8') . " DEL {$inicioTexto} AL {$finTexto}";
    }
    public function generarExcel()
    {
        try {
            $resumenTramoServicio = new ResumenTramoServicio();
            $resumenTramoServicio->procesarPago($this->resumenPorTramo,$this->listaPago,$this->periodo);
            $this->obtenerListaResumen();
            $this->alert('success', 'Reporte generado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-pago-component');
    }
}
