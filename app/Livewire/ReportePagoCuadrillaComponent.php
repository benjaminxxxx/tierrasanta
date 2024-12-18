<?php

namespace App\Livewire;

use App\Exports\ReportePagoCuadrillaExport;
use App\Exports\ReportePagoCuadrillaSheetExport;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuadrillaHora;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use Livewire\Component;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportePagoCuadrillaComponent extends Component
{
    use LivewireAlert;
    public $dateRange;
    public $fechaInicio;
    public $fechaFin;
    public $gruposTrabajando = [];
    public $grupoSeleccionado;
    public $cuadrilleros = [];
    public $fechas;

    protected $listeners = ['pagoRegistrado' => 'cargarCuadrilla'];

    public function updatedDateRange()
    {
        try {
            $this->gruposTrabajando = [];
            $this->cuadrilleros = [];

            if ($this->dateRange) {

                [$startDate, $endDate] = explode(' to ', $this->dateRange);
                $this->fechaInicio = Carbon::parse($startDate);
                $this->fechaFin = Carbon::parse($endDate);
                $this->buscarGruposTrabajaron($this->fechaInicio, $this->fechaFin);
            } else {
                $this->alert('error', 'No se recibió ningún rango de fechas.');
            }
        } catch (\Exception $e) {

            $this->dispatch('log', $e->getMessage());
            $this->alert('error', 'Ocurrió un error al procesar el rango de fechas. Por favor verifica el formato.');
        }
    }
    public function updatedGrupoSeleccionado()
    {
        $this->cuadrilleros = [];
    }
    public function buscarGruposTrabajaron($fechaInicio, $fechaFin)
    {

        // Obtiene los IDs de las semanas que coinciden con el rango de fechas
        $semanas = CuaAsistenciaSemanal::where(function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]) // La semana empieza dentro del rango
                ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin]) // La semana termina dentro del rango
                ->orWhere(function ($query) use ($fechaInicio, $fechaFin) {
                    // La semana abarca completamente el rango
                    $query->where('fecha_inicio', '<=', $fechaInicio)
                        ->where('fecha_fin', '>=', $fechaFin);
                });
        })->pluck('id'); // Obtiene solo los IDs

        // Obtiene los códigos de grupos únicos de las semanas seleccionadas
        $gruposCodigos = CuaAsistenciaSemanalGrupo::whereIn('cua_asi_sem_id', $semanas)
            ->distinct()
            ->pluck('gru_cua_cod');

        // Recupera la información detallada de los grupos con los códigos obtenidos
        $this->gruposTrabajando = CuaGrupo::whereIn('codigo', $gruposCodigos)->get();
    }
    public function cargarCuadrilla()
    {
        try {
            if (!$this->fechaInicio || !$this->fechaFin) {
                throw new Exception("Falta una de las fechas");
            }

            // Generar el rango de fechas
            $this->fechas = collect();
            $inicio = Carbon::parse($this->fechaInicio);
            $fin = Carbon::parse($this->fechaFin);

            while ($inicio->lte($fin)) {
                $this->fechas->push($inicio->format('Y-m-d'));
                $inicio->addDay();
            }

            // Construir la consulta base
            $query = CuadrillaHora::with([
                'asistenciaSemanalCuadrillero.cuadrillero',
                'asistenciaSemanalCuadrillero.asistenciaSemanalGrupo',
            ])->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);

            // Filtrar por grupo seleccionado si aplica
            if ($this->grupoSeleccionado) {
                $query->whereHas('asistenciaSemanalCuadrillero.asistenciaSemanalGrupo', function ($q) {
                    $q->where('gru_cua_cod', $this->grupoSeleccionado);
                });
            }

            // Obtener los datos
            $cuadrillaHoras = $query->get();

            // Estructurar los datos
            $this->cuadrilleros = $this->estructurarCuadrilleros($cuadrillaHoras, $this->fechas);
        } catch (\Exception $e) {
            $this->dispatch('log', $e->getMessage());
            $this->alert('error', 'Ocurrió un error al procesar obtener la cuadrilla.');
        }
    }
    private function estructurarCuadrilleros(Collection $cuadrillaHoras, Collection $fechas)
    {
        $resultado = [];

        foreach ($cuadrillaHoras as $hora) {
            $cuadrilleroId = $hora->asistenciaSemanalCuadrillero->cuadrillero->id;

            // Si el cuadrillero no está en el arreglo, inicializamos
            if (!isset($resultado[$cuadrilleroId])) {

                $cuadrillero = Cuadrillero::find($cuadrilleroId);
                if (!$cuadrillero) {
                    return;
                }
                $resultadoPago = $cuadrillero->obtenerPago($this->fechaInicio, $this->fechaFin);

                //$resultado['saldo_acumulado'];
                $estaCancelado = $resultadoPago['esta_cancelado'];
                $montoPagado = $resultadoPago['monto_pagado'];

                $resultado[$cuadrilleroId] = [
                    'cuadrillero_id' => $hora->asistenciaSemanalCuadrillero->cuadrillero->id,
                    'empleado' => $hora->asistenciaSemanalCuadrillero->cuadrillero->nombres,
                    'grupo_trabajo' => $hora->asistenciaSemanalCuadrillero->asistenciaSemanalGrupo->grupo->nombre ?? null,
                    'grupo_color' => $hora->asistenciaSemanalCuadrillero->asistenciaSemanalGrupo->grupo->color ?? null,
                    'grupo_codigo' => $hora->asistenciaSemanalCuadrillero->asistenciaSemanalGrupo->grupo->codigo ?? null,
                    'monto_total' => 0,
                    'monto_pagado' => $montoPagado,
                    'esta_cancelado' => $estaCancelado,
                ];

                // Inicializar los montos diarios en 0
                foreach ($fechas as $fecha) {
                    $resultado[$cuadrilleroId][$fecha] = 0;
                }
            }

            // Asignar el monto diario
            $resultado[$cuadrilleroId][$hora->fecha] += $hora->costo_dia + $hora->bono;

            // Sumar al total
            $resultado[$cuadrilleroId]['monto_total'] += $hora->costo_dia + $hora->bono;
        }

        return array_values($resultado); // Retornar un array numerado
    }
    public function exportarReporte()
    {
        $grupoFiltrado = 'TODOS';

        $grupo = CuaGrupo::find($this->grupoSeleccionado);
        if ($grupo) {
            $grupoFiltrado = $grupo['nombre'];
        }

        $totalRegistrosPagados = count(array_filter($this->cuadrilleros, function ($cuadrillero) {
            return isset($cuadrillero['esta_cancelado']) && $cuadrillero['esta_cancelado'] === true;
        }));

        $data = [
            'cuadrilleros' => [
                'cuadrilleros' => $this->cuadrilleros,
                'informacionHeader' => [
                    'fecha_inicio' => $this->fechaInicio,
                    'fecha_fin' => $this->fechaFin,
                    'grupo' => $grupoFiltrado,
                    'total_registros' => count($this->cuadrilleros),
                    'total_registros_pagados' => $totalRegistrosPagados,
                ],
            ],
            'pagos' => [
                'fecha_inicio' => $this->fechaInicio,
                'fecha_fin' => $this->fechaFin,
            ]
        ];

        return Excel::download(new ReportePagoCuadrillaExport($data), 'ReportePagoCuadrilla.xlsx');
    }
    public function render()
    {
        return view('livewire.reporte-pago-cuadrilla-component');
    }
}
