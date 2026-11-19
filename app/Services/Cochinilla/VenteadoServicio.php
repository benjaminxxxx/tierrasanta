<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaVenteado;
use App\Exceptions\ErrorControlado;
use App\Services\Cochinilla\Lote\CochinillaLoteProceso;
use App\Support\FormatoHelper;
use DB;

class VenteadoServicio
{
    public function paginarFiltradosHuerfanos(array $filtros, int $perPage = 15)
    {
        return CochinillaVenteado::query()
            ->whereNull('cochinilla_ingreso_id')
            ->when($filtros['lote'] ?? null, fn($q, $l) => $q->where('lote', $l))
            ->when($filtros['anio'] ?? null, fn($q, $a) => $q->whereYear('fecha_proceso', $a))
            ->orderByDesc('lote')
            ->paginate($perPage);
    }
    public function paginarIngresosConVenteados(array $filtros, int $perPage = 15)
    {
        return CochinillaIngreso::query()
            ->whereHas('venteados')
            ->with([
                'venteados' => function ($q) use ($filtros) {
                    if (!empty($filtros['anio'])) {
                        $q->whereYear('fecha_proceso', $filtros['anio']);
                    }
                }
            ])
            ->when($filtros['lote'] ?? null, fn($q, $l) => $q->where('lote', $l))
            ->when($filtros['campo'] ?? null, fn($q, $c) => $q->where('campo', $c))
            ->orderByDesc('lote')
            ->paginate($perPage);
    }
    /**
     * Guarda el venteado de la tabla. Con ingreso reemplaza el del lote; si ya tenía datos y cambian, pide motivo
     * y deja el antes/después en la auditoría (la tabla vacía elimina el venteado del lote).
     */
    public function registrarVenteado(array $datos, ?int $loteFijo = null, ?int $ingresoId = null, ?string $motivo = null): int
    {
        return DB::transaction(function () use ($datos, $loteFijo, $ingresoId, $motivo) {

            $rows = [];

            foreach ($datos as $fila) {

                if (empty($fila['fecha_proceso']) && empty($fila['fecha_de_proceso'])) {
                    continue;
                }

                $rows[] = $this->prepararFilaVenteado($fila, $loteFijo);
            }

            $ingreso = $ingresoId ? CochinillaIngreso::find($ingresoId) : null;
            $antes = $ingreso ? CochinillaLoteProceso::lineasVenteado($ingreso->venteados()->get()) : [];
            $despues = CochinillaLoteProceso::lineasVenteado($rows);
            if (!$rows && !$antes) {
                throw new ErrorControlado('No hay datos para registrar.');
            }
            $cambia = $antes && $antes !== $despues;
            if ($cambia) {
                $motivo = CochinillaLoteProceso::exigirMotivo($motivo);
            }

            if ($ingreso) {
                $ingreso->venteados()->delete();
            }
            if ($rows) {
                CochinillaVenteado::insert($rows);
            }
            if ($cambia) {
                CochinillaLoteProceso::auditarProceso($ingreso->id, (int) $ingreso->lote, 'Venteado', $antes, $despues, $motivo);
            }

            return count($rows);
        });
    }
    private function prepararFilaVenteado(array $fila, ?int $loteFijo): array
    {
        $kilos = (float) ($fila['kilos_ingresado'] ?? $fila['kilos_ingresados'] ?? 0);
        $limpia = (float) ($fila['limpia'] ?? 0);
        $polvillo = (float) ($fila['polvillo'] ?? 0);

        $suma = round($limpia + $polvillo, 2);

        if (round($kilos, 2) < $suma) {
            throw new \Exception('Error de peso en lote: ' . ($loteFijo ?? $fila['lote']));
        }

        return [
            'lote' => $loteFijo ?? (int) $fila['lote'],
            'fecha_proceso' => FormatoHelper::parseFecha(
                $fila['fecha_proceso'] ?? $fila['fecha_de_proceso']
            ),
            'kilos_ingresado' => $kilos,
            'limpia' => $limpia,
            'polvillo' => $polvillo,
            // cochinilla_ingreso_id se resolverá por trigger
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
