<?php

namespace App\Services\Caja\Arqueo;

use App\Models\CajaArqueo;
use App\Models\CajaFuente;
use App\Services\Caja\Movimiento\CajaMovimientoValidador;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Arqueo: el dinero de caja está repartido (oficina AQP, caja naranja, Flavia, cuadrillas…); el saldo que
 * queda en cada fuente se registra para comparar lo que realmente hay con el disponible de caja.
 */
class CajaArqueoCrud
{
    public function __construct(private CajaMovimientoValidador $validador)
    {
    }

    /**
     * @param array<int|string, mixed> $montos caja_fuente_id => monto (vacío = no se registró esa fuente)
     */
    public function guardar(?int $id, string $fecha, array $montos, ?string $observacion): CajaArqueo
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $this->validador->asegurarMesAbierto($fecha, 'registrar arqueos');
        $montos = array_filter($montos, fn($m) => $m !== null && $m !== '');
        if (!$montos) {
            throw ValidationException::withMessages(['montos' => 'Registra el saldo de al menos una fuente.']);
        }
        foreach ($montos as $fuenteId => $monto) {
            if (!is_numeric($monto) || !CajaFuente::whereKey($fuenteId)->exists()) {
                throw ValidationException::withMessages(['montos' => 'Hay un monto o una fuente no válidos.']);
            }
        }

        return DB::transaction(function () use ($id, $fecha, $montos, $observacion) {
            $arqueo = $id ? CajaArqueo::findOrFail($id) : new CajaArqueo(['creado_por' => auth()->id()]);
            if ($id) {
                $this->validador->asegurarMesAbierto($arqueo->fecha, 'editar sus arqueos');
            }
            $arqueo->fill(['fecha' => $fecha, 'observacion' => $observacion ?: null])->save();
            $arqueo->detalles()->whereNotIn('caja_fuente_id', array_keys($montos))->delete();
            foreach ($montos as $fuenteId => $monto) {
                $arqueo->detalles()->updateOrCreate(['caja_fuente_id' => $fuenteId], ['monto' => round((float) $monto, 2)]);
            }
            AuditoriaServicio::registrar(CajaArqueo::class, $arqueo->id, $id ? 'editar' : 'crear', null,
                ['fecha' => $fecha, 'montos' => $montos, 'observacion' => $observacion]);
            return $arqueo;
        });
    }

    public function eliminar(int $id): void
    {
        $arqueo = CajaArqueo::with('detalles')->findOrFail($id);
        $this->validador->asegurarMesAbierto($arqueo->fecha, 'eliminar sus arqueos');
        AuditoriaServicio::registrar(CajaArqueo::class, $arqueo->id, 'eliminar', $arqueo->toArray());
        $arqueo->delete();
    }
}
