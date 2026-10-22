<?php

namespace App\Services\Cochinilla\Excel;

use Illuminate\Support\Facades\DB;

/** Catálogos del Excel de cochinilla: campos, observaciones (código ↔ descripción), campaña vigente por campo y fecha. */
class CochinillaExcelCatalogoConsulta
{
    private ?array $campanias = null;
    private ?array $observaciones = null;

    /** @return array<string, string> código => descripción */
    public function observaciones(): array
    {
        return $this->observaciones ??= DB::table('cochinilla_observaciones')->orderBy('descripcion')->pluck('descripcion', 'codigo')->all();
    }

    /** @return string[] */
    public function campos(): array
    {
        return DB::table('campos')->orderBy('orden')->orderBy('nombre')->pluck('nombre')->map(fn($c) => (string) $c)->all();
    }

    private ?array $aliasCampos = null;
    private ?array $areas = null;

    public function area(?string $campo): ?float
    {
        $this->areas ??= DB::table('campos')->pluck('area', 'nombre')->all();
        return isset($this->areas[$campo]) ? (float) $this->areas[$campo] : null;
    }

    /**
     * Nombre oficial del campo: tal cual, o por sus alias (campos.alias, separados por coma: "Naranjos B" → NaranjosB,
     * "Limonero 5.A" → Limonero5A). Sin coincidencia devuelve el texto como vino (el importador lo marca).
     */
    public function campo(?string $texto): ?string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }
        if ($this->aliasCampos === null) {
            $this->aliasCampos = [];
            foreach (DB::table('campos')->get(['nombre', 'alias']) as $c) {
                $this->aliasCampos[mb_strtolower(trim($c->nombre))] = $c->nombre;
                foreach (array_filter(array_map('trim', explode(',', (string) $c->alias))) as $a) {
                    $this->aliasCampos[mb_strtolower($a)] ??= $c->nombre;
                }
            }
        }
        return $this->aliasCampos[mb_strtolower($texto)] ?? $texto;
    }

    /** Nombre de la campaña vigente del campo en la fecha (la misma regla que los triggers). */
    public function campania(?string $campo, ?string $fecha): ?string
    {
        if (!$campo || !$fecha) {
            return null;
        }
        $this->campanias ??= DB::table('campos_campanias')->orderByDesc('fecha_inicio')
            ->get(['campo', 'nombre_campania', 'fecha_inicio', 'fecha_fin'])->groupBy('campo')->all();
        foreach ($this->campanias[$campo] ?? [] as $c) {
            if (substr($c->fecha_inicio, 0, 10) <= $fecha && (!$c->fecha_fin || substr($c->fecha_fin, 0, 10) >= $fecha)) {
                return $c->nombre_campania;
            }
        }
        return null;
    }

    /** Listas desplegables del Excel: clave => [título, valores]. */
    public function listas(): array
    {
        return [
            'campos' => ['Campos', $this->campos()],
            'observaciones' => ['Observaciones (ingreso)', array_values($this->observaciones())],
        ];
    }
}
