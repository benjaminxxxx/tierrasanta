<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Services\Cuadrilla\Reporte\RptCuadrillaPagosXTramo;
use App\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos;
use App\Services\Cuadrilla\TramoLaboral\ResumenTramoServicio;
use Carbon\CarbonPeriod;
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

    public function cerrarModal()
    {
        $this->mostrarFormularioReportePago = false;
        $this->reset(['listaPago', 'periodo', 'pagarJornal']);
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

    private function obtenerListaResumen()
    {
        $resultado = app(ListaAcumuladaTramos::class)->obtenerListaCuadrilleros($this->resumenPorTramo, true);
        $this->periodo = $resultado['periodo'];
        
        $this->listaPago = collect($resultado['listaPago'])
            ->filter(function ($personal) {
                return !empty($personal['nombres']);
            })
            ->toArray();
    }


    private function obtenerTitulo($modalidadPago, $fechaInicio, $fechaFin)
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        $inicioTexto = mb_strtoupper($inicio->translatedFormat('j \d\e F'), 'UTF-8');
        $finTexto = mb_strtoupper($fin->translatedFormat('j \d\e F'), 'UTF-8');

        return 'CUADRILLA ' . mb_strtoupper($modalidadPago, 'UTF-8') . " DEL {$inicioTexto} AL {$finTexto}";
    }

    public function generarExcel()
    {
        try {
            $codigoGrupo = $this->resumenPorTramo->grupo_codigo;

            $listaPagoFiltrada = collect($this->listaPago)
                ->filter(fn($personal) => !empty($personal['nombres']))
                ->toArray();

            foreach ($listaPagoFiltrada as $cuadrilleroId => $personal) {
                foreach ($this->periodo as $fecha) {
                    $valores = $personal[$fecha] ?? null;

                    if (!$valores || !is_array($valores)) {
                        continue;
                    }

                    $estaPagado = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $bonoPagado = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('costo_dia', '>', 0)
                        ->update(['esta_pagado' => (int) $estaPagado]);

                    CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('total_bono', '>', 0)
                        ->update(['bono_esta_pagado' => (int) $bonoPagado]);
                }
            }

            $rptCuadrillaPagoXTramo = new RptCuadrillaPagosXTramo();
            
            $listaFiltrada = collect($listaPagoFiltrada)->map(function ($personal) {
                $nuevo = [];

                foreach ($personal as $clave => $valores) {
                    if (!is_array($valores)) {
                        $nuevo[$clave] = $valores;
                        continue;
                    }

                    $costo = (float) ($valores['costo_dia'] ?? 0);
                    $bono = (float) ($valores['total_bono'] ?? 0);

                    $estaPagadoJornal = (bool) ($valores['esta_pagado'] ?? false);
                    $estaPagadoBono = (bool) ($valores['bono_esta_pagado'] ?? false);

                    if (!$estaPagadoJornal) {
                        $costo = 0;
                    }
                    if (!$estaPagadoBono) {
                        $bono = 0;
                    }

                    if ($costo <= 0) {
                        $estaPagadoJornal = false;
                    }
                    if ($bono <= 0) {
                        $estaPagadoBono = false;
                    }

                    if (!$estaPagadoJornal && !$estaPagadoBono) {
                        continue;
                    }

                    $nuevo[$clave] = [
                        'costo_dia' => $costo,
                        'total_bono' => $bono,
                        'esta_pagado' => $estaPagadoJornal,
                        'bono_esta_pagado' => $estaPagadoBono,
                    ];
                }

                return $nuevo;
            })->toArray();

            $data = [
                'resumen_tramo' => $this->resumenPorTramo,
                'pagar_bonos' => true,
                'lista_pago' => $listaFiltrada,
                'fecha_inicio' => $this->fechaInicioPago,
                'fecha_reporte' => now()->format('Y-m-d'),
            ];

            return $rptCuadrillaPagoXTramo->descargarReporte($data);

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-pago-component');
    }
}
