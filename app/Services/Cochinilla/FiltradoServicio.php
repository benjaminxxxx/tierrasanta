<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaFiltrado;
use App\Models\CochinillaIngreso;
use App\Support\FormatoHelper;
use DB;

class FiltradoServicio
{
    public function paginarFiltradosHuerfanos(array $filtros, int $perPage = 15)
    {
        return CochinillaFiltrado::query()
            ->whereNull('cochinilla_ingreso_id')
            ->when($filtros['lote'] ?? null, fn($q, $l) => $q->where('lote', $l))
            ->when($filtros['anio'] ?? null, fn($q, $a) => $q->whereYear('fecha_proceso', $a))
            ->orderByDesc('lote')
            ->paginate($perPage);
    }
    public function paginarIngresosConFiltrados(array $filtros, int $perPage = 15)
    {
        return CochinillaIngreso::query()
            ->whereHas('filtrados')
            ->with([
                'filtrados' => function ($q) use ($filtros) {
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
     * Lógica CRUD: Guarda o actualiza los registros de filtrado.
     * Soporta tanto el Formulario Livewire como el Seeder (Excel).
     */
    public function registrarFiltrados(array $datos, ?int $loteFijo = null): int
    {
        return DB::transaction(function () use ($datos, $loteFijo) {

            $rows = [];

            foreach ($datos as $fila) {
                if (empty($fila['fecha_proceso']) && empty($fila['fecha_de_proceso'])) {
                    continue;
                }

                $rows[] = $this->prepararFila($fila, $loteFijo);
            }

            if ($rows) {
                CochinillaFiltrado::insert($rows);
            }

            return count($rows);
        });
    }
    /**
     * Subproceso de Cálculo: Valida consistencia y calcula la basura si falta.
     * Este sector es "solo cálculo", fácil de mover a una clase Support luego.
     */
    private function prepararFila(array $fila, ?int $loteFijo): array
    {
        $kilos = (float) ($fila['kilos_ingresados'] ?? 0);
        $p1 = (float) ($fila['primera'] ?? 0);
        $p2 = (float) ($fila['segunda'] ?? 0);
        $p3 = (float) ($fila['tercera'] ?? 0);
        $piedra = (float) ($fila['piedra'] ?? 0);

        $suma = round($p1 + $p2 + $p3 + $piedra, 2);

        if (round($kilos, 2) < $suma) {
            throw new \Exception('Error de peso en lote: ' . ($loteFijo ?? $fila['lote']));
        }

        return [
            'lote' => $loteFijo ?? (int) $fila['lote'],
            'fecha_proceso' => FormatoHelper::parseFecha(
                $fila['fecha_proceso'] ?? $fila['fecha_de_proceso']
            ),
            'kilos_ingresados' => $kilos,
            'primera' => $p1,
            'segunda' => $p2,
            'tercera' => $p3,
            'piedra' => $piedra,
            // cochinilla_ingreso_id lo resolverá el trigger / job
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
