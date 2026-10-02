<?php

namespace App\Services\Caja\Cierre;

use App\Models\CajaCierre;
use App\Models\CajaMovimiento;
use Illuminate\Support\Carbon;

/** Estado de cierre de cada mes de caja. */
class CajaCierreConsulta
{
    public function estaCerrado(int $anio, int $mes): bool
    {
        return CajaCierre::where('anio', $anio)->where('mes', $mes)->where('estado', CajaCierre::CERRADO)->exists();
    }

    public function fechaCerrada($fecha): bool
    {
        $f = Carbon::parse($fecha);
        return $this->estaCerrado($f->year, $f->month);
    }

    public function cierre(int $anio, int $mes): ?CajaCierre
    {
        return CajaCierre::with('cerradoPor:id,name')->where('anio', $anio)->where('mes', $mes)->first();
    }

    /**
     * Meses ya terminados (desde el primer movimiento hasta el mes anterior a $hasta) que no están cerrados.
     *
     * @return array<int, array{anio:int, mes:int}>
     */
    public function mesesSinCerrar(?Carbon $hasta = null): array
    {
        $primera = CajaMovimiento::min('fecha');
        if (!$primera) {
            return [];
        }
        $hasta = ($hasta ?? now())->copy()->startOfMonth();
        $cerrados = CajaCierre::where('estado', CajaCierre::CERRADO)->get(['anio', 'mes'])
            ->map(fn($c) => sprintf('%04d-%02d', $c->anio, $c->mes))->flip();

        $pendientes = [];
        for ($m = Carbon::parse($primera)->startOfMonth(); $m->lt($hasta); $m->addMonth()) {
            if (!isset($cerrados[$m->format('Y-m')])) {
                $pendientes[] = ['anio' => $m->year, 'mes' => $m->month];
            }
        }
        return $pendientes;
    }
}
