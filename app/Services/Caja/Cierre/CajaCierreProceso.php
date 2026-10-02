<?php

namespace App\Services\Caja\Cierre;

use App\Models\CajaCierre;
use App\Models\CajaMovimiento;
use App\Services\Caja\Movimiento\CajaMovimientoConsulta;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cierre y reapertura del mes de caja. Cerrado, nada del mes se puede crear, editar ni eliminar.
 * Reabrir es un caso raro (corregir un error) y exige un motivo, que queda en la auditoría.
 */
class CajaCierreProceso
{
    public function __construct(private CajaMovimientoConsulta $consulta, private CajaCierreConsulta $cierres)
    {
    }

    public function cerrar(int $anio, int $mes): CajaCierre
    {
        $inicio = Carbon::create($anio, $mes, 1);
        if ($inicio->gt(now()->startOfMonth())) {
            throw ValidationException::withMessages(['mes' => 'No se puede cerrar un mes que aún no empieza.']);
        }
        if ($this->cierres->estaCerrado($anio, $mes)) {
            throw ValidationException::withMessages(['mes' => 'La caja de ese mes ya está cerrada.']);
        }

        return DB::transaction(function () use ($anio, $mes, $inicio) {
            $fin = $inicio->copy()->endOfMonth()->toDateString();
            $cierre = CajaCierre::updateOrCreate(['anio' => $anio, 'mes' => $mes], [
                'estado' => CajaCierre::CERRADO,
                'saldo_final' => $this->consulta->disponibleAl($fin),
                'movimientos' => CajaMovimiento::whereBetween('fecha', [$inicio->toDateString(), $fin])->count(),
                'cerrado_por' => auth()->id(),
                'cerrado_at' => now(),
            ]);
            AuditoriaServicio::registrar(CajaCierre::class, $cierre->id, 'editar', ['estado' => CajaCierre::ABIERTO],
                ['estado' => CajaCierre::CERRADO, 'saldo_final' => $cierre->saldo_final], "Cierre de caja {$mes}/{$anio}");
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
            AuditoriaServicio::registrar(CajaCierre::class, $cierre->id, 'editar', ['estado' => CajaCierre::CERRADO],
                ['estado' => CajaCierre::ABIERTO], "Reapertura de caja {$mes}/{$anio}: {$motivo}");
            return $cierre;
        });
    }
}
