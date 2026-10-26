<?php

namespace App\Services\Costos\Mensual;

use App\Models\CostoMensual;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Guarda los costos fijos y operativos de un mes (tabla de Costos → Mensual). */
class CostosMensualProceso
{
    /**
     * @param array<int, array{concepto: string, blanco: mixed, negro: mixed}> $filas
     */
    public function guardar(int $anio, int $mes, array $filas): CostoMensual
    {
        if ($mes < 1 || $mes > 12 || $anio < 2000) {
            throw ValidationException::withMessages(['mes' => 'El mes seleccionado no es válido.']);
        }

        return DB::transaction(function () use ($anio, $mes, $filas) {
            $costo = CostoMensual::where('anio', $anio)->where('mes', $mes)->lockForUpdate()->first();
            $antes = $costo?->only($this->columnas());

            $datos = [];
            $errores = [];
            foreach ($filas as $i => $fila) {
                $col = $fila['concepto'] ?? null;
                if (!isset(CostosMensualReglas::CONCEPTOS[$col])) {
                    continue;
                }
                // La mano de obra indirecta calculada solo cambia al recalcular
                if ($col === CostosMensualReglas::MOI && $costo?->moi_calculado_en) {
                    continue;
                }
                foreach (['blanco', 'negro'] as $parte) {
                    $valor = $this->numero($fila[$parte] ?? null);
                    if ($valor === false) {
                        $errores[] = CostosMensualReglas::CONCEPTOS[$col][1] . " ({$parte}): no es un número.";
                        continue;
                    }
                    if ($valor < 0) {
                        $errores[] = CostosMensualReglas::CONCEPTOS[$col][1] . " ({$parte}): no puede ser negativo.";
                        continue;
                    }
                    $datos["{$col}_{$parte}"] = $valor;
                }
            }
            if ($errores) {
                throw ValidationException::withMessages(['costos' => $errores]);
            }

            $costo = $costo
                ? tap($costo)->update($datos)
                : CostoMensual::create(['anio' => $anio, 'mes' => $mes] + $datos);

            AuditoriaServicio::registrar(CostoMensual::class, $costo->id, $antes ? 'editar' : 'crear', $antes,
                $costo->fresh()->only($this->columnas()), sprintf('Costos fijos y operativos %02d/%d', $mes, $anio));

            return $costo;
        });
    }

    private function columnas(): array
    {
        return collect(array_keys(CostosMensualReglas::CONCEPTOS))
            ->flatMap(fn($c) => ["{$c}_blanco", "{$c}_negro"])->all();
    }

    /** Número o 0 si viene vacío; false si no se entiende. */
    private function numero(mixed $v): float|false
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return round((float) $v, 2);
        }
        $limpio = str_replace([',', 'S/', ' '], '', (string) $v);
        return is_numeric($limpio) ? round((float) $limpio, 2) : false;
    }
}
