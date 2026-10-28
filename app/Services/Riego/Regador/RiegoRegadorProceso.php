<?php

namespace App\Services\Riego\Regador;

use App\Exceptions\ErrorControlado;
use App\Services\Riego\RiegoServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agrega regadores a una fecha. Valida que cada uno pueda regar ese día (planillero con contrato agrario vigente,
 * cuadrillero activo) por si la fecha cambió después de elegirlos.
 */
class RiegoRegadorProceso
{
    /**
     * @param array<int, array{id:int, tipo:string, nombre?:string}> $regadores tipo: empleados | cuadrilleros
     * @return int regadores agregados
     */
    public function agregar(string $fecha, array $regadores): int
    {
        if (!$regadores) {
            throw new ErrorControlado('Elige uno o más regadores.');
        }
        $f = Carbon::parse($fecha)->toDateString();
        $validos = collect(RiegoRegadorConsulta::planilleros($f))->merge(RiegoRegadorConsulta::cuadrilleros($f))
            ->keyBy(fn($t) => $t['tipo'] . ':' . $t['id']);
        $noPueden = collect($regadores)->reject(fn($r) => $validos->has(($r['tipo'] ?? '') . ':' . ($r['id'] ?? '')))
            ->map(fn($r) => $r['nombre'] ?? "#{$r['id']}")->all();
        if ($noPueden) {
            throw new ErrorControlado('No pueden regar el ' . Carbon::parse($f)->format('d/m/Y') . ' (sin contrato agrario vigente o ya registrados): '
                . implode(', ', $noPueden) . '.');
        }
        DB::transaction(fn() => RiegoServicio::registrarRegadoresEnFecha($f, $regadores));
        return count($regadores);
    }

    /**
     * Cuadrilleros elegidos que parecen ser trabajadores de planilla con contrato ese día (caso "RICHARD ALVAREZ" ↔
     * "ALVAREZ CHAVEZ, RICHARD"): su riego debe ir a su nombre de planilla.
     *
     * @return array<int, array{cuadrillero_id:int, cuadrillero:string, empleado_id:int, empleado:string}>
     */
    public function cuadrillerosQueSonDePlanilla(string $fecha, array $regadores): array
    {
        $f = Carbon::parse($fecha)->toDateString();
        $personas = app(\App\Services\Cuadrilla\Persona\CuadrillaPersonaConsulta::class);
        $sospechosos = [];
        foreach ($regadores as $r) {
            if (($r['tipo'] ?? '') !== 'cuadrilleros' || !($cu = \App\Models\Cuadrillero::find($r['id'] ?? 0))) {
                continue;
            }
            if ($emp = $personas->empleadoConContrato($cu, $f)) {
                $sospechosos[] = ['cuadrillero_id' => $cu->id, 'cuadrillero' => $cu->nombres, 'empleado_id' => $emp['id'], 'empleado' => $emp['nombre']];
            }
        }
        return $sospechosos;
    }
}
