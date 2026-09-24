<?php
// app/Services/GestionCuadrilla/DesgloseServicio.php
namespace App\Services\GestionCuadrilla;

use App\Models\Desglose;
use App\Models\DesgloseDetalle;
use Illuminate\Support\Facades\DB;

class DesgloseServicio
{
    public function crear(array $datos): Desglose
    {
        return DB::transaction(function () use ($datos) {
            $saldoAnterior = (float) ($datos['saldo_anterior'] ?? $this->obtenerSaldoAnteriorSugerido());
            $montoInicial = (float) ($datos['monto_inicial'] ?? 0);

            return Desglose::create([
                'codigo_vale' => $datos['codigo_vale'] ?? null,
                'fecha' => $datos['fecha'],
                'monto_inicial' => $montoInicial,
                'saldo_anterior' => $saldoAnterior,
                'monto_total_gastos' => 0,
                'saldo_final' => $saldoAnterior + $montoInicial,
                'entregado_por' => $datos['entregado_por'] ?? null,
                'recibido_por' => $datos['recibido_por'] ?? null,
                'estado' => 'abierto',
            ]);
        });
    }
    public function actualizar(Desglose $desglose, array $datos): Desglose
    {
        return DB::transaction(function () use ($desglose, $datos) {
            $desglose->update([
                'codigo_vale' => $datos['codigo_vale'] ?? null,
                'fecha' => $datos['fecha'],
                'monto_inicial' => (float) $datos['monto_inicial'],
                'saldo_anterior' => (float) $datos['saldo_anterior'],
                'entregado_por' => $datos['entregado_por'] ?? null,
                'recibido_por' => $datos['recibido_por'] ?? null,
            ]);

            // monto_inicial/saldo_anterior cambiaron -> saldoBase() cambió -> toda la cadena
            // de saldo_resultante de los detalles existentes queda desactualizada.
            $this->recalcularSaldos($desglose->fresh());

            return $desglose->fresh();
        });
    }
    public function obtenerSaldoAnteriorSugerido(): float
    {
        $ultimo = Desglose::orderByDesc('fecha')->orderByDesc('id')->first();

        return (float) ($ultimo->saldo_final ?? 0);
    }

    public function agregarDetalle(Desglose $desglose, array $datos): DesgloseDetalle
    {
        return DB::transaction(function () use ($desglose, $datos) {
            $ultimoSaldo = $desglose->detalles()->orderByDesc('id')->value('saldo_resultante')
                ?? $this->saldoBase($desglose);

            $detalle = $desglose->detalles()->create([
                'nro_documento' => $datos['nro_documento'] ?? null,
                'referencia_nro_caja' => $datos['referencia_nro_caja'] ?? null,
                'razon_social' => $datos['razon_social'] ?? null,
                'tipo_gasto' => $datos['tipo_gasto'],
                'descripcion' => $datos['descripcion'],
                'monto' => $datos['monto'],
                'saldo_resultante' => $ultimoSaldo - $datos['monto'],
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $this->actualizarAgregados($desglose);

            return $detalle;
        });
    }

    public function eliminarDetalle(DesgloseDetalle $detalle): void
    {
        DB::transaction(function () use ($detalle) {
            $desglose = $detalle->desglose;

            $this->revertirVinculos($detalle);

            $detalle->delete();
            $this->recalcularSaldos($desglose);
        });
    }

    public function eliminarDesglose(Desglose $desglose): void
    {
        DB::transaction(function () use ($desglose) {
            // Antes de borrar en cascada, cada detalle debe soltar sus vínculos
            // (si no, los CuadRegistroDiario/gastos/bonos quedarían con esta_pagado=true
            // apuntando a un desglose_detalle_id que ya no existe)
            foreach ($desglose->detalles as $detalle) {
                $this->revertirVinculos($detalle);
            }

            $desglose->delete(); // cascade elimina los detalles (FK onDelete cascade)
        });
    }

    protected function revertirVinculos(DesgloseDetalle $detalle): void
    {
        $detalle->gastosAdicionales()->update([
            'esta_pagado' => false,
            'desglose_detalle_id' => null,
        ]);

        $detalle->registrosDiarios()->update([
            'esta_pagado' => false,
            'desglose_detalle_id' => null,
        ]);

        $detalle->actividadesBonos()->update([
            'esta_pagado' => false,
            'desglose_detalle_id' => null,
        ]);
    }

    protected function saldoBase(Desglose $desglose): float
    {
        return (float) $desglose->saldo_anterior + (float) $desglose->monto_inicial;
    }

    protected function recalcularSaldos(Desglose $desglose): void
    {
        $saldo = $this->saldoBase($desglose);

        foreach ($desglose->detalles()->orderBy('id')->get() as $detalle) {
            $saldo -= (float) $detalle->monto;
            $detalle->update(['saldo_resultante' => $saldo]);
        }

        $this->actualizarAgregados($desglose->fresh());
    }

    protected function actualizarAgregados(Desglose $desglose): void
    {
        $totalGastos = (float) $desglose->detalles()->sum('monto');

        $desglose->update([
            'monto_total_gastos' => $totalGastos,
            'saldo_final' => $this->saldoBase($desglose) - $totalGastos,
        ]);
    }
}