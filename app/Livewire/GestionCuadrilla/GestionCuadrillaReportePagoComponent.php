<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Services\Cuadrilla\Reporte\RptCuadrillaPagosXTramo;
use App\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos;
use App\Services\Cuadrilla\TramoLaboral\ResumenTramoServicio;
use Carbon\CarbonPeriod;
use DB;
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

            $codigoGrupo = $this->resumenPorTramo->grupo_codigo;
            $tramoLaboral = $this->resumenPorTramo->tramo;

            foreach ($this->listaPago as $cuadrilleroId => $personal) {
                foreach ($this->periodo as $fecha) {
                    $valores = $personal[$fecha] ?? null;

                    if (!$valores || !is_array($valores)) {
                        continue;
                    }

                    // normalizar a boolean (acepta 0,1,true,false)
                    $estaPagado = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $bonoPagado = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    // === Jornal ===
                    $jornal = CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('costo_dia', '>', 0)
                        ->first();

                    if ($jornal) {
                        if (is_null($jornal->tramo_pagado_jornal_id)) {
                            // nunca pagado -> registrar nuevo estado
                            $jornal->esta_pagado = $estaPagado;
                            $jornal->tramo_pagado_jornal_id = $estaPagado ? $tramoLaboral->id : null;
                            $jornal->save();
                        } elseif ($jornal->tramo_pagado_jornal_id == $tramoLaboral->id) {
                            // mismo tramo -> permitir cambios
                            $jornal->esta_pagado = $estaPagado;
                            $jornal->tramo_pagado_jornal_id = $estaPagado ? $tramoLaboral->id : null;
                            $jornal->save();
                        }
                    }

                    // === Bono ===
                    $bono = CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('total_bono', '>', 0)
                        ->first();

                    if ($bono) {
                        if (is_null($bono->tramo_pagado_bono_id)) {
                            $bono->bono_esta_pagado = $bonoPagado;
                            $bono->tramo_pagado_bono_id = $bonoPagado ? $tramoLaboral->id : null;
                            $bono->save();
                        } elseif ($bono->tramo_pagado_bono_id == $tramoLaboral->id) {
                            $bono->bono_esta_pagado = $bonoPagado;
                            $bono->tramo_pagado_bono_id = $bonoPagado ? $tramoLaboral->id : null;
                            $bono->save();
                        }
                    }
                }
            }


            $rptCuadrillaPagoXTramo = new RptCuadrillaPagosXTramo();
            $listaFiltrada = collect($this->listaPago)
                ->map(function ($personal) {
                    $fechasFiltradas = collect($personal)
                        ->filter(function ($valores, $clave) {
                            if (!is_array($valores))
                                return true;

                            $costo = (float) ($valores['costo_dia'] ?? 0);
                            $bono = (float) ($valores['total_bono'] ?? 0);
                            $estaPagadoJornal = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $estaPagadoBono = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                            return ($estaPagadoJornal && $costo > 0) || ($estaPagadoBono && $bono > 0);
                        });

                    $nuevo = $fechasFiltradas->mapWithKeys(function ($valores, $clave) {
                        if (!is_array($valores))
                            return [$clave => $valores];

                        $costo = (float) ($valores['costo_dia'] ?? 0);
                        $bono = (float) ($valores['total_bono'] ?? 0);
                        $estaPagadoJornal = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $estaPagadoBono = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                        return [
                            $clave => [
                                'costo_dia' => $estaPagadoJornal ? $costo : 0,
                                'total_bono' => $estaPagadoBono ? $bono : 0,
                                'esta_pagado' => $estaPagadoJornal,
                                'bono_esta_pagado' => $estaPagadoBono,
                            ]
                        ];
                    });

                    // Recalcular totales basados en fechas filtradas
                    $totales = $nuevo->filter(fn($v) => is_array($v))->reduce(function ($carry, $f) {
                        $carry['monto'] = ($carry['monto'] ?? 0) + ($f['costo_dia'] ?? 0);
                        $carry['bono'] = ($carry['bono'] ?? 0) + ($f['total_bono'] ?? 0);
                        $carry['total'] = ($carry['total'] ?? 0) + (($f['costo_dia'] ?? 0) + ($f['total_bono'] ?? 0));
                        return $carry;
                    }, []);

                    $nuevo['monto'] = $totales['monto'] ?? 0;
                    $nuevo['bono'] = $totales['bono'] ?? 0;
                    $nuevo['total'] = $totales['total'] ?? 0;

                    return $nuevo;
                })
                ->filter(fn($personal) => collect($personal)->filter(fn($v) => is_array($v))->isNotEmpty())
                ->toArray();


            $data = [
                'resumen_tramo' => $this->resumenPorTramo,
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
