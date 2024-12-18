<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use App\Models\PagoCuadrilla;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PagoCuadrillaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $mesContableSeleccionado;
    public $anioContableSeleccionado;
    public $anios;
    public $cuadrillero;
    public $estadoPago;
    public $montoAPagarContable;
    public $montoAPagar;
    public $fechaInicio;
    public $fechaFin;
    public $pagosRealizados;
    public $saldoAcumulado = 0;
    public $estaCancelado = false;
    public $montoPagado = 0;
    protected $listeners = ['realizarPagoCuadrillero'];
    public function mount()
    {
        $this->resetForm();
    }
    public function realizarPagoCuadrillero($cuadrilleroId, $fechaInicio, $fechaFin)
    {
        try {
            $this->resetForm();
            $this->cuadrillero = Cuadrillero::find($cuadrilleroId);
            if (!$this->cuadrillero) {
                throw new Exception("El cuadrillero ya no existe");
            }

            $this->fechaInicio = Carbon::parse($fechaInicio);
            $this->fechaFin =  Carbon::parse($fechaFin);
            $existenErrores = $this->validarCruceDeFechas($cuadrilleroId, $this->fechaInicio, $this->fechaFin);

            if ($existenErrores) {
                return $this->alert('error', $existenErrores, [
                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
            }
            
            $this->cargarPagosRealizados();
            $this->determinarAnios($fechaInicio, $fechaFin);
            $this->sugerirMesAnioContable($fechaFin);
            
            $this->montoAPagar = $this->cuadrillero->determinarPago($fechaInicio, $fechaFin);
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Se ha presentado un error en el servidor.');
        }
    }
    public function eliminarPago($pagoId){
        try {
            $pago = PagoCuadrilla::findOrFail($pagoId);
            $pago->delete();
            $this->cargarPagosRealizados();
            $this->alert('success', 'Se ha eliminado el pago con exito.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Se ha presentado un error al eliminar el pago.');
        }
    }
    public function cargarPagosRealizados(){
        try {
            if (!$this->cuadrillero) {
                throw new Exception("El cuadrillero ya no existe");
            }

            $resultado = $this->cuadrillero->obtenerPago($this->fechaInicio, $this->fechaFin);
            
            $this->pagosRealizados = $resultado['lista_pagos']; 
            $this->saldoAcumulado = $resultado['saldo_acumulado'];
            $this->estaCancelado = $resultado['esta_cancelado'];
            $this->montoPagado = $resultado['monto_pagado'];

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Se ha presentado un error al cargar los pagos.');
        }
    }
    public function sugerirMesAnioContable($fechaFin)
    {
        // Convertir la fecha de fin a un objeto Carbon
        $fin = Carbon::parse($fechaFin);

        // Establecer los valores del mes y año contable basados en la fecha de fin
        $this->mesContableSeleccionado = $fin->month; // Mes de la fecha de fin
        $this->anioContableSeleccionado = $fin->year; // Año de la fecha de fin
    }
    public function realizarPago()
    {
        $this->validate(
            [
                'montoAPagarContable' => 'required|numeric|min:0.01',
                'anioContableSeleccionado' => 'required|integer',
                'mesContableSeleccionado' => 'required|integer',
                'estadoPago' => 'required',
            ],
            [
                'montoAPagarContable.required' => 'El monto contable es obligatorio.',
                'montoAPagarContable.numeric' => 'El monto contable debe ser un número.',
                'montoAPagarContable.min' => 'El monto contable debe ser mayor a 0.',
                'anioContableSeleccionado.required' => 'Debe seleccionar un año contable.',
                'anioContableSeleccionado.integer' => 'El año contable debe ser un valor válido.',
                'mesContableSeleccionado.required' => 'Debe seleccionar un mes contable.',
                'mesContableSeleccionado.integer' => 'El mes contable debe ser un valor válido.',
                'estadoPago.required' => 'El estado de pago es obligatorio.',
            ]
        );

        try {
            if (!$this->cuadrillero) {
                throw new Exception("El cuadrillero ya no existe");
            }
            $saldoPendiente = $this->montoAPagar['monto_a_pagar'] - $this->montoAPagarContable - $this->saldoAcumulado;
            PagoCuadrilla::create([
                'cuadrillero_id' => $this->cuadrillero->id,
                'monto_trabajado' => $this->montoAPagar['monto_a_pagar'],
                'monto_pagado' => $this->montoAPagarContable,
                'saldo_pendiente' => $this->estadoPago=='pago_parcial'?$saldoPendiente:0,
                'fecha_inicio' => $this->fechaInicio->format('Y-m-d'),
                'fecha_fin' => $this->fechaFin->format('Y-m-d'),
                'fecha_pago' => Carbon::now()->format('Y-m-d'),
                'anio_contable' => $this->anioContableSeleccionado,
                'mes_contable' => $this->mesContableSeleccionado,
                'estado' => $this->estadoPago,
                //'pago_referencia_id',
                'creado_por' => Auth::id(),
                //'actualizado_por'
            ]);
            $this->alert('success', 'Se ha realizado el pago con exito.');
            $this->dispatch('pagoRegistrado');
            $this->cargarPagosRealizados();
            
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Se ha presentado un error al realizar el pago.');
        }
    }
    private function validarCruceDeFechas($cuadrilleroId, $fechaInicio, $fechaFin)
    {
        // Buscar pagos existentes que se crucen con las fechas proporcionadas
        $pagosConflicto = PagoCuadrilla::where('cuadrillero_id', $cuadrilleroId)
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_inicio', '<=', $fechaInicio)
                            ->where('fecha_fin', '>=', $fechaFin);
                    });
            })
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                // Excluir pagos con las mismas fechas exactas
                $query->where('fecha_inicio', '!=', $fechaInicio)
                    ->orWhere('fecha_fin', '!=', $fechaFin);
            })
            ->get();

        // Si hay pagos en conflicto, lanzar excepción
        if ($pagosConflicto->isNotEmpty()) {
            $mensajeErrores = $pagosConflicto->map(function ($pago) {
                return "Conflicto con pago existente: desde el {$pago->fecha_inicio} hasta el {$pago->fecha_fin}.";
            })->implode(' ');

            return $mensajeErrores;
        }
        return null;
    }
    public function determinarAnios($fechaInicio, $fechaFin)
    {
        // Convertir fechas a objetos Carbon
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        // Determinar el año inicial
        $anioInicio = ($inicio->month === 1) ? $inicio->year - 1 : $inicio->year;

        // Determinar el año final
        $anioFin = ($fin->month === 12) ? $fin->year + 1 : $fin->year;

        // Generar el rango de años
        $this->anios = range($anioInicio, $anioFin);
    }
    public function resetForm()
    {
        $this->montoAPagar = [
            'total_horas' => 0,
            'monto_a_pagar' => 0,
        ];
        $this->anios = [];
        $this->pagosRealizados = null;
        $this->montoAPagarContable = 0;
        $this->saldoAcumulado = 0;
        $this->estadoPago = 'pago_completo';
        $this->reset(['cuadrillero', 'mesContableSeleccionado', 'anioContableSeleccionado']);
    }
    public function render()
    {
        return view('livewire.pago-cuadrilla-form-component');
    }
}
