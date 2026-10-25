<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Collection;

class DataPlanillaHelper
{
    /**
     * Compara los datos del sistema con los del Excel.
     */
    public static function detectarCambios(array $dataOriginal, array $dataExcel): array
    {
        $resultado = [
            'EMPLEADOS' => ['new' => [], 'update' => [], 'deleted' => []],
            'CONTRATACIONES' => ['new' => [], 'update' => [], 'deleted' => []],
            'SUELDOS' => ['new' => [], 'update' => [], 'deleted' => []],
            'HIJOS' => ['new' => [], 'update' => [], 'deleted' => []],
        ];

        // 1. PROCESAR EMPLEADOS
        $originalEmps = collect($dataOriginal['EMPLEADOS'])->keyBy('documento');
        $excelEmps = collect($dataExcel['EMPLEADOS']);

        foreach ($excelEmps as $emp) {
            $dni = $emp['documento'];
            if (!$originalEmps->has($dni)) {
                $resultado['EMPLEADOS']['new'][] = $emp;
            } else {
                $original = $originalEmps->get($dni);
                $cambios = self::obtenerDiferencias($original, $emp);
                
                if (!empty($cambios)) {
                    $resultado['EMPLEADOS']['update'][] = array_merge($emp, ['original_id' => $original['id'], 'changes' => $cambios]);
                }
            }
        }
        $resultado['EMPLEADOS']['deleted'] = $originalEmps->whereNotIn('documento', $excelEmps->pluck('documento'))->values()->toArray();
/*
        // 2. PROCESAR TABLAS RELACIONALES
        $tablas = [
            'CONTRATACIONES' => ['ref' => 'documento', 'original' => 'CONTRATACIONES'],
            'SUELDOS' => ['ref' => 'documento', 'original' => 'SUELDOS'],
            'HIJOS' => ['ref' => 'documento_padre', 'original' => 'HIJOS'],
        ];

        foreach ($tablas as $key => $config) {
            $excelGrp = collect($dataExcel[$key])
                ->map(fn($f) => self::mapearColumnas($f, $equivalencias[$key]))
                ->groupBy($config['ref']);

            $originalGrp = collect($dataOriginal[$config['original']])->groupBy($config['ref']);

            foreach ($excelGrp as $dni => $filasExcel) {
                $filasOriginales = $originalGrp->get($dni, collect());

                foreach ($filasExcel as $index => $filaExcel) {
                    if ($filasOriginales->has($index)) {
                        $original = $filasOriginales->get($index);
                        $cambios = self::obtenerDiferencias($original, $filaExcel);

                        if (!empty($cambios)) {
                            dd($original, $filaExcel, $cambios);
                            $resultado[$key]['update'][] = array_merge($filaExcel, [
                                'original_id' => $original['id'],
                                'changes' => $cambios
                            ]);
                        }
                    } else {
                        $resultado[$key]['new'][] = $filaExcel;
                    }
                }

                if ($filasOriginales->count() > $filasExcel->count()) {
                    $eliminados = $filasOriginales->slice($filasExcel->count());
                    foreach ($eliminados as $e) {
                        $resultado[$key]['deleted'][] = $e;
                    }
                }
            }

            $dnisSoloEnOriginal = $originalGrp->keys()->diff($excelGrp->keys());
            foreach ($dnisSoloEnOriginal as $dni) {
                foreach ($originalGrp->get($dni) as $e) {
                    $resultado[$key]['deleted'][] = $e;
                }
            }
        }*/

        return $resultado;
    }
    private static function obtenerDiferencias(array $original, array $nuevo): array
    {
        $cambios = [];
        foreach ($nuevo as $key => $valor) {
            if (!array_key_exists($key, $original))
                continue;


            $valOriginal = self::normalizar($original[$key], $key);
            $valNuevo = self::normalizar($valor, $key);

            // Si después de normalizar son diferentes, registramos el cambio
            if ($valOriginal !== $valNuevo) {
                if ($key == 'esta_jubilado') {
                    $valOriginal = self::normalizar($original[$key], $key);
                    $valNuevo = self::normalizar($valor, $key);
                    dd($valOriginal, $valNuevo, $valor, $key);
                }
                // Guardamos el valor ORIGINAL para mostrarlo en el preview "Antes: X"
                $cambios[$key] = $valOriginal;
            }
        }
        return $cambios;
    }

    /**
     * Normalización para comparación y preparación para DB
     */
    private static function normalizar($valor, $key = '')
    {
        // Definimos las llaves que deben comportarse como booleanos/flags
        $isBooleanKey = in_array($key, ['esta_jubilado', 'jubilado', 'esta_estudiando']);

        // 1. Manejo de Nulos y Vacíos
        if (is_null($valor) || $valor === '' || strtolower($valor) === 'null') {
            // Si es una llave booleana, el vacío cuenta como 0 (NO), no como NULL
            return $isBooleanKey ? 0 : null;
        }

        // 2. Fechas: Excel (int) -> SQL (string Y-m-d)
        if (str_contains($key, 'fecha')) {
            if (is_numeric($valor)) {
                return Carbon::instance(Date::excelToDateTimeObject($valor))->format('Y-m-d');
            }
            return substr($valor, 0, 10);
        }

        // 3. Booleanos / Flags (SI/NO, 1/0, S/N)
        if ($isBooleanKey) {
            if (is_bool($valor))
                return $valor ? 1 : 0;

            $v = strtoupper(trim($valor));
            if ($v === 'SI' || $v === '1' || $v === 'S')
                return 1;
            if ($v === 'NO' || $v === '0' || $v === 'N')
                return 0;

            return 0;
        }

        // 4. Números (DNI, montos, etc.)
        if (is_numeric($valor)) {
            return (string) (float) $valor;
        }

        // 5. Strings generales
        return mb_strtoupper(trim($valor), 'UTF-8');
    }

}