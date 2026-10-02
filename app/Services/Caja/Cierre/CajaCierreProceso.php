<?php

namespace App\Services\Caja\Cierre;

use App\Models\CajaCierre;
use App\Models\CajaCierreEvento;
use App\Models\CajaMovimiento;
use App\Services\Caja\Movimiento\CajaMovimientoConsulta;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cierre y reapertura del mes de caja. Cerrado, nada del mes se puede crear, editar ni eliminar.
 * Reabrir es un caso raro (corregir un error) y exige un motivo.
 *
 * caja_cierres guarda el estado actual del mes; cada cierre y reapertura queda además como evento en
 * caja_cierre_eventos (quién, cuándo, con qué saldo y por qué), para el historial de caja.
 */
class CajaCierreProceso
{
    public function __construct(private CajaMovimientoConsulta $consulta, private CajaCierreConsulta $cierres)
    {
    }

    public function cerrar(int $anio, int $mes, ?string $observacion = null): CajaCierre
    {
        $inicio = Carbon::create($anio, $mes, 1);
        if ($inicio->gt(now()->startOfMonth())) {
            throw ValidationException::withMessages(['mes' => 'No se puede cerrar un mes que aún no empieza.']);
        }
        if ($this->cierres->estaCerrado($anio, $mes)) {
            throw ValidationException::withMessages(['mes' => 'La caja de ese mes ya está cerrada.']);
        }
        $observacion = trim((string) $observacion) ?: null;

        return DB::transaction(function () use ($anio, $mes, $inicio, $observacion) {
            $fin = $inicio->copy()->endOfMonth()->toDateString();
            $saldo = $this->consulta->disponibleAl($fin);
            $movimientos = CajaMovimiento::whereBetween('fecha', [$inicio->toDateString(), $fin])->count();

            $cierre = CajaCierre::updateOrCreate(['anio' => $anio, 'mes' => $mes], [
                'estado' => CajaCierre::CERRADO,
                'saldo_final' => $saldo,
                'movimientos' => $movimientos,
                'cerrado_por' => auth()->id(),
                'cerrado_at' => now(),
            ]);
            $this->evento($cierre, 'cerrado', $saldo, $movimientos, $observacion);
            AuditoriaServicio::registrar(CajaCierre::class, $cierre->id, 'editar', ['estado' => CajaCierre::ABIERTO],
                ['estado' => CajaCierre::CERRADO, 'saldo_final' => $saldo], "Cierre de caja {$mes}/{$anio}" . ($observacion ? ": {$observacion}" : ''));
            return $cierre;
        });
    }

    public function reabrir(int $anio, int $mes, string $motivo): CajaCierre
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 5) {
            throw ValidationException::withMessages(['motivo' => 'Indica el motivo de la reapertura (qué hay que corregir).']);
        }
        $cierre = CajaCierre::where('anio', $anio)->where('mes', $mes)->where('estado', CajaCierre::CERRADO)->first();
        if (!$cierre) {
            throw ValidationException::withMessages(['mes' => 'La caja de ese mes no está cerrada.']);
        }

        return DB::transaction(function () use ($cierre, $motivo, $anio, $mes) {
            $cierre->update([
                'estado' => CajaCierre::ABIERTO,
                'reabierto_por' => auth()->id(),
                'reabierto_at' => now(),
                'motivo_reapertura' => $motivo,
            ]);
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
            $this->evento($cierre, 'reabierto', $this->consulta->disponibleAl($fin), (int) $cierre->movimientos, $motivo);
            AuditoriaServicio::registrar(CajaCierre::class, $cierre->id, 'editar', ['estado' => CajaCierre::CERRADO],
                ['estado' => CajaCierre::ABIERTO], "Reapertura de caja {$mes}/{$anio}: {$motivo}");
            return $cierre;
        });
    }

    private function evento(CajaCierre $cierre, string $accion, float $saldo, int $movimientos, ?string $motivo): void
    {
        CajaCierreEvento::create([
            'caja_cierre_id' => $cierre->id,
            'anio' => $cierre->anio,
            'mes' => $cierre->mes,
            'accion' => $accion,
            'saldo' => $saldo,
            'movimientos' => $movimientos,
            'motivo' => $motivo ? mb_substr($motivo, 0, 500) : null,
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()?->name,
        ]);
    }
}
