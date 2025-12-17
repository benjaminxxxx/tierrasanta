<?php

namespace App\Services\Evaluacion;

use App\Models\CampoCampania;
use App\Models\EvalInfestacionPenca;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use DomainException;

class EvaluacionInfestacionPencaServicio
{
    /**
     * Guarda la evaluación de infestación por pencas.
     *
     * @throws InvalidArgumentException
     * @throws DomainException
     */
    public function guardar(CampoCampania $campania, array $filas): void
    {
        if (!$campania->exists) {
            throw new DomainException('La campaña no es válida.');
        }

        DB::transaction(function () use ($campania, $filas) {

            foreach ($filas as $fila) {

                if (!isset($fila['n_pencas'])) {
                    throw new DomainException('Fila sin número de penca.');
                }

                EvalInfestacionPenca::updateOrCreate(
                    [
                        'campo_campania_id' => $campania->id,
                        'numero_penca' => (int) $fila['n_pencas'],
                    ],
                    [
                        'eval_primera_piso_2' => $this->normalizarEntero($fila['eval_primera_piso_2'] ?? null),
                        'eval_primera_piso_3' => $this->normalizarEntero($fila['eval_primera_piso_3'] ?? null),

                        'eval_segunda_piso_2' => $this->normalizarEntero($fila['eval_segunda_piso_2'] ?? null),
                        'eval_segunda_piso_3' => $this->normalizarEntero($fila['eval_segunda_piso_3'] ?? null),

                        'eval_tercera_piso_2' => $this->normalizarEntero($fila['eval_tercera_piso_2'] ?? null),
                        'eval_tercera_piso_3' => $this->normalizarEntero($fila['eval_tercera_piso_3'] ?? null),
                    ]
                );
            }
        });
    }

    private function normalizarEntero($valor): ?int
    {
        if ($valor === '' || $valor === null) {
            return null;
        }

        if (!is_numeric($valor)) {
            throw new DomainException('Valor no numérico en evaluación.');
        }

        return (int) $valor;
    }

    /**
     * Genera la data para Handsontable.
     *
     * @throws DomainException
     */
    public function generar(CampoCampania $campania, int $totalPencas = 14): array
    {
        if (!$campania->exists) {
            throw new DomainException('La campaña no es válida.');
        }

        // Inicializar estructura base
        $mapa = [];

        for ($i = 1; $i <= $totalPencas; $i++) {
            $mapa[$i] = [
                'n_pencas' => $i,

                'eval_primera_piso_2' => null,
                'eval_primera_piso_3' => null,

                'eval_segunda_piso_2' => null,
                'eval_segunda_piso_3' => null,

                'eval_tercera_piso_2' => null,
                'eval_tercera_piso_3' => null,
            ];
        }

        // Traer datos existentes
        $registros = EvalInfestacionPenca::where(
            'campo_campania_id',
            $campania->id
        )->get();

        foreach ($registros as $registro) {
            $n = $registro->numero_penca;

            if (!isset($mapa[$n])) {
                continue;
            }

            $mapa[$n]['eval_primera_piso_2'] = $registro->eval_primera_piso_2;
            $mapa[$n]['eval_primera_piso_3'] = $registro->eval_primera_piso_3;

            $mapa[$n]['eval_segunda_piso_2'] = $registro->eval_segunda_piso_2;
            $mapa[$n]['eval_segunda_piso_3'] = $registro->eval_segunda_piso_3;

            $mapa[$n]['eval_tercera_piso_2'] = $registro->eval_tercera_piso_2;
            $mapa[$n]['eval_tercera_piso_3'] = $registro->eval_tercera_piso_3;
        }

        return array_values($mapa);
    }
}
