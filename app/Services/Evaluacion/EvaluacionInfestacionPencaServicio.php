<?php

namespace App\Services\Evaluacion;

use App\Models\CampoCampania;
use App\Models\EvalInfestacionPenca;
use Exception;
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
    /*
    public function guardar(CampoCampania $campania, array $filas): void
    {
        if (!$campania->exists) {
            throw new DomainException('La campaña no es válida.');
        }

        DB::transaction(function () use ($campania, $filas) {

            foreach ($filas as $fila) {

                if (!isset($fila['n_pencas'])) {
                    throw new Exception('Fila sin número de penca.');
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
    }*/
    public function guardar(CampoCampania $campania, array $filas): void
    {
        if (!$campania->exists) {
            throw new DomainException('La campaña no es válida.');
        }

        DB::transaction(function () use ($campania, $filas) {

            // 1. Obtener los números de penca que el usuario está enviando (los que se quedan)
            // Usamos array_filter por si acaso llega alguna fila vacía o mal estructurada
            $pencasPresentes = array_map(function ($fila) {
                if (!isset($fila['n_pencas'])) {
                    throw new Exception('Fila sin número de penca.');
                }
                return (int) $fila['n_pencas'];
            }, $filas);

            // 2. ELIMINAR de la base de datos las pencas que YA NO ESTÁN en el envío
            EvalInfestacionPenca::where('campo_campania_id', $campania->id)
                ->whereNotIn('numero_penca', $pencasPresentes)
                ->delete();

            // 3. Proceder con el updateOrCreate para las filas que sí quedan
            foreach ($filas as $fila) {
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
    /*
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
    }*/
    /**
     * Genera la data para Handsontable de manera limpia y automática.
     *
     * @throws DomainException
     */
    public function generar(CampoCampania $campania, int $totalPencas = 14): array
    {
        if (!$campania->exists) {
            throw new DomainException('La campaña no es válida.');
        }

        // Traer únicamente los datos que ya existen en la base de datos
        $registros = EvalInfestacionPenca::where('campo_campania_id', $campania->id)
            ->orderBy('numero_penca', 'asc')
            ->get();

        $mapa = [];

        // Si ya hay registros guardados, estructuramos solo lo que existe
        foreach ($registros as $registro) {
            $mapa[] = [
                'n_pencas' => $registro->numero_penca,
                'eval_primera_piso_2' => $registro->eval_primera_piso_2,
                'eval_primera_piso_3' => $registro->eval_primera_piso_3,
                'eval_segunda_piso_2' => $registro->eval_segunda_piso_2,
                'eval_segunda_piso_3' => $registro->eval_segunda_piso_3,
                'eval_tercera_piso_2' => $registro->eval_tercera_piso_2,
                'eval_tercera_piso_3' => $registro->eval_tercera_piso_3,
            ];
        }

        // Si la campaña es nueva y está completamente vacía, dejamos que Handsontable 
        // maneje sus filas vacías por defecto o inicializamos una sola estructura vacía
        if (empty($mapa)) {
            return [];
        }

        return $mapa;
    }
}
