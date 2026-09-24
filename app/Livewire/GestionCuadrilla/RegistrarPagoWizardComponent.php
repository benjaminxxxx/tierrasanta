<?php
// app/Livewire/GestionCuadrilla/RegistrarPagoWizardComponent.php
namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadActividadBono;
use App\Models\CuadRegistroDiario;
use App\Models\Desglose;
use App\Models\DesgloseDetalle;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Services\GestionCuadrilla\DesgloseServicio;
use App\Services\GestionCuadrilla\PagoBonoCuadrillaServicio;
use App\Services\GestionCuadrilla\PagoCuadrillaServicio;
use App\Services\GestionCuadrilla\PagoGastoAdicionalServicio;
use App\Services\GestionCuadrilla\RangoPeriodoServicio;
use App\Traits\HandlesAlerts;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class RegistrarPagoWizardComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    public bool $mostrar = false;
    public int $paso = 1;

    // Reutiliza el mismo vocabulario que desglose_detalles.tipo_gasto
    public ?string $tipoPago = null; // PAGO_CUADRILLA | PAGO_BONOS_ACUMULADOS | GASTO_ADICIONAL
    public ?string $tipoPeriodo = null; // SEMANAL | QUINCENAL | MENSUAL
    public string $modoRango = 'actual'; // actual | personalizado

    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?int $anioSeleccionado = null;
    public ?int $mesSeleccionado = null;
    public $matrizPago = [];
    public array $gruposDisponibles = [];
    public array $seleccionados = [];
    public string $documento = '';
    public string $descripcion = '';
    public $desgloseId;
    public $detalles = [];
    public ?string $fechaReferencia = null;

    #[On('abrirRegistradorDePagos')]
    public function abrir($desgloseId): void
    {
        $desglose = Desglose::findOrFail($desgloseId);

        $this->desgloseId = $desgloseId;
        $this->fechaReferencia = $desglose->fecha->toDateString();

        $this->reset(['paso', 'tipoPago', 'tipoPeriodo', 'modoRango', 'fechaInicio', 'fechaFin', 'anioSeleccionado', 'mesSeleccionado']);
        $this->paso = 1;
        $this->mostrar = true;
    }

    public function cerrar(): void
    {
        $this->mostrar = false;
    }

    public function seleccionarTipoPago(string $tipo): void
    {
        $this->tipoPago = $tipo;
        $this->paso = 2;
    }

    public function seleccionarTipoPeriodo(string $tipo, RangoPeriodoServicio $servicio): void
    {
        $this->tipoPeriodo = $tipo;
        $this->modoRango = 'actual';
        $this->cargarRangoActual($servicio);
        $this->paso = 3;
    }

    public function cambiarModoRango(string $modo, RangoPeriodoServicio $servicio): void
    {
        $this->modoRango = $modo;

        if ($modo === 'actual') {
            $this->cargarRangoActual($servicio);
        } else {
            $this->fechaInicio = null;
            $this->fechaFin = null;
            $this->anioSeleccionado = (int) now()->format('Y');
            $this->mesSeleccionado = (int) now()->format('n');
        }
    }


    protected function cargarRangoActual(RangoPeriodoServicio $servicio): void
    {
        [$inicio, $fin] = match ($this->tipoPeriodo) {
            'SEMANAL' => $servicio->semanaDe($this->fechaReferencia),
            'QUINCENAL' => $servicio->quincenaDe($this->fechaReferencia),
            'MENSUAL' => $servicio->mesAnteriorDe($this->fechaReferencia),
        };

        $this->fechaInicio = $inicio;
        $this->fechaFin = $fin;
    }

    public function confirmarRango(RangoPeriodoServicio $servicio): void
    {
        if ($this->modoRango === 'personalizado') {
            if ($this->tipoPeriodo === 'MENSUAL') {
                $this->validate([
                    'anioSeleccionado' => ['required', 'integer', 'min:2020'],
                    'mesSeleccionado' => ['required', 'integer', 'between:1,12'],
                ]);

                [$this->fechaInicio, $this->fechaFin] = $servicio->mesEspecifico(
                    $this->anioSeleccionado,
                    $this->mesSeleccionado
                );
            } else {
                $this->validate([
                    'fechaInicio' => ['required', 'date'],
                    'fechaFin' => ['required', 'date', 'after_or_equal:fechaInicio'],
                ]);
            }
        }

        // A partir de aquí sigue la carga de la tabla de totalizados (paso 4),
        // pendiente de que me indiques la lógica.
        $this->cargarDetalles();
        $this->paso = 4;
    }

    public function cargarDetalles()
    {
        // PAGO_CUADRILLA | PAGO_BONOS_ACUMULADOS | GASTO_ADICIONAL
        //PAGO_CUADRILLA_EXTRAS
        switch ($this->tipoPago) {
            case 'PAGO_CUADRILLA':
            case 'PAGO_CUADRILLA_EXTRAS':
                $this->matrizPago = PagoCuadrillaServicio::calcularPagoJornal(
                    $this->tipoPago,
                    $this->tipoPeriodo,
                    $this->fechaInicio,
                    $this->fechaFin
                );
                
                // 2. Asignar los grupos disponibles actualizados a la propiedad pública
                $this->gruposDisponibles = $this->matrizPago['grupos_disponibles'] ?? [];

                // 3. Preseleccionar automáticamente los grupos restantes que aún no se pagan
                $this->seleccionados = array_column($this->gruposDisponibles, 'codigo_grupo');
                break;
            case 'GASTO_ADICIONAL':
                $resultado = PagoGastoAdicionalServicio::calcularGastosAdicionales(
                    $this->tipoPeriodo,
                    $this->fechaInicio,
                    $this->fechaFin
                );
                $this->matrizPago = null;
                $this->detalles = $resultado['detalles'];
                break;
            case 'PAGO_BONOS_ACUMULADOS':
                $resultado = PagoBonoCuadrillaServicio::calcularPagoBonoCuadrilla(
                    $this->tipoPeriodo,
                    $this->fechaInicio,
                    $this->fechaFin
                );

                $this->matrizPago = $resultado;
                //  dd($this->matrizPago);
                $inicio = Carbon::parse($this->fechaInicio)->locale('es');
                $fin = Carbon::parse($this->fechaFin)->locale('es');

                $periodoTexto = strtoupper($this->tipoPeriodo);
                $rangoFechas = $inicio->format('d') . ' al ' . $fin->format('d') . ' de ' . $fin->translatedFormat('F');

                // Ejemplo generado: "Bono SEMANAL Santa Rita del 01 al 07 de Enero"
                $this->descripcion = "Bono {$periodoTexto} Santa Rita del {$rangoFechas}";
                //dd($this->matrizPago);
                break;
            default:
                # code...
                break;
        }

    }
    public function confirmarRegistroPago(DesgloseServicio $servicio)
    {
        try {
            $desglose = Desglose::findOrFail($this->desgloseId);

            match ($this->tipoPago) {
                'PAGO_CUADRILLA', 'PAGO_CUADRILLA_EXTRAS' => $this->confirmarPagoCuadrilla($servicio, $desglose),
                'GASTO_ADICIONAL' => $this->confirmarPagoGastosAdicionales($servicio, $desglose),
                'PAGO_BONOS_ACUMULADOS' => $this->confirmarPagoBonosAcumulados($servicio, $desglose),
                default => throw new Exception("Tipo de pago no reconocido: {$this->tipoPago}"),
            };

            $this->successAlert('Pago confirmado exitosamente.');

            $this->documento = '';
            $this->descripcion = '';
            $this->cargarDetalles();
            $this->dispatch('pagoRegistrado', desgloseId: $this->desgloseId);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    protected function confirmarPagoBonosAcumulados(DesgloseServicio $servicio, Desglose $desglose): void
    {
        $actividadBonoIds = $this->matrizPago['total_actividades_bono_ids'] ?? [];
        $montoTotal = (float) ($this->matrizPago['gran_total'] ?? 0);

        if (empty($actividadBonoIds) || $montoTotal <= 0) {
            throw new Exception('No hay bonos acumulados pendientes de pago para el periodo seleccionado.');
        }

        DB::transaction(function () use ($servicio, $desglose, $actividadBonoIds, $montoTotal) {

            // 1. Crear el registro consolidado en desglose_detalles
            $desgloseDetalle = $servicio->agregarDetalle($desglose, [
                'nro_documento' => $this->documento ?: null,
                'descripcion' => $this->descripcion,
                'tipo_gasto' => 'PAGO_CUADRILLA', // O el tipo de gasto correspondiente en tu enum
                'monto' => round($montoTotal, 2),
                'observaciones' => 'BONO_' . strtoupper($this->tipoPeriodo),
            ]);

            // 2. Marcar como pagadas todas las actividades de bonos correspondientes
            CuadActividadBono::whereIn('id', $actividadBonoIds)->update([
                'esta_pagado' => true,
                'desglose_detalle_id' => $desgloseDetalle->id,
            ]);
        });
    }
    protected function confirmarPagoCuadrilla(DesgloseServicio $servicio, Desglose $desglose): void
    {
        if (empty($this->seleccionados)) {
            throw new Exception('Debe seleccionar al menos un grupo.');
        }

        $gruposDisponibles = $this->matrizPago['grupos_disponibles'] ?? [];

        $registroDiarioIds = [];
        $actividadBonoIds = [];
        $montoTotalSeleccionado = 0.0;

        foreach ($gruposDisponibles as $grupo) {
            if (in_array($grupo['codigo_grupo'], $this->seleccionados)) {
                $registroDiarioIds = array_merge($registroDiarioIds, $grupo['registro_diario_ids']);
                $actividadBonoIds = array_merge($actividadBonoIds, $grupo['actividad_bono_ids']);
                $montoTotalSeleccionado += (float) $grupo['monto_total'];
            }
        }

        $registroDiarioIds = array_values(array_unique($registroDiarioIds));
        $actividadBonoIds = array_values(array_unique($actividadBonoIds));

        if (empty($registroDiarioIds)) {
            throw new Exception('No hay registros pendientes de pago para los grupos seleccionados.');
        }

        DB::transaction(function () use ($servicio, $desglose, $registroDiarioIds, $actividadBonoIds, $montoTotalSeleccionado) {
            // PAGO_CUADRILLA_EXTRAS no existe en el enum de desglose_detalles: se guarda como PAGO_CUADRILLA
            // (la distinción "extras/turno tarde" queda en la descripción, no en el tipo_gasto)
            $desgloseDetalle = $servicio->agregarDetalle($desglose, [
                'nro_documento' => $this->documento ?: null,
                'descripcion' => $this->descripcion,
                'tipo_gasto' => 'PAGO_CUADRILLA',
                'monto' => round($montoTotalSeleccionado, 2),
                'observaciones' => $this->tipoPeriodo,
            ]);

            CuadRegistroDiario::whereIn('id', $registroDiarioIds)->update([
                'esta_pagado' => true,
                'desglose_detalle_id' => $desgloseDetalle->id,
            ]);

            if (!empty($actividadBonoIds)) {
                CuadActividadBono::whereIn('id', $actividadBonoIds)->update([
                    'esta_pagado' => true,
                    'desglose_detalle_id' => $desgloseDetalle->id,
                ]);
            }
        });
    }

    protected function confirmarPagoGastosAdicionales(DesgloseServicio $servicio, Desglose $desglose): void
    {
        if (empty($this->detalles)) {
            throw new Exception('No hay gastos pendientes de pago en este rango.');
        }

        DB::transaction(function () use ($servicio, $desglose) {
            // A diferencia de cuadrilla (1 detalle agregado de los grupos seleccionados),
            // aquí cada fila de $this->detalles YA es un detalle independiente (por descripción+grupo).
            foreach ($this->detalles as $fila) {
                if (empty($fila['gasto_ids'])) {
                    continue;
                }

                $desgloseDetalle = $servicio->agregarDetalle($desglose, [
                    'nro_documento' => $fila['nro_documento'] ?: null,
                    'descripcion' => $fila['descripcion'],
                    'tipo_gasto' => 'GASTO_ADICIONAL',
                    'monto' => round($fila['monto'], 2),
                ]);

                GastoAdicionalPorGrupoCuadrilla::whereIn('id', $fila['gasto_ids'])->update([
                    'esta_pagado' => true,
                    'desglose_detalle_id' => $desgloseDetalle->id,
                ]);
            }
        });
    }
    public function volver(): void
    {
        $this->paso = max(1, $this->paso - 1);
    }
    public function verDetalleDia($registroDiarioId)
    {
        // Carga o emite el evento para inspeccionar las horas y bonos de ese día específico
        $this->dispatch('abrir-modal-detalle-dia', registroId: $registroDiarioId);
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.registrar-pago-wizard-component');
    }
}